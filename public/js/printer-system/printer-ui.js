
(function() {
    const registerPrinterSystem = () => {
        if (!Alpine.data('printerSystem')) {
            Alpine.data('printerSystem', () => ({
            printers: {},
            isOpen: false,
            sources: Array.isArray(window.PRINTER_SOURCES) ? window.PRINTER_SOURCES : [],

            init() {
                // Register listeners first to catch events from configureSources/loadSavedDevices
                this.onRestored = (event) => {
                    const role = event.detail?.role;
                    const device = event.detail?.device;
                    if (!role || !this.printers[role]) return;
                    this.printers[role].deviceName = device?.name || 'Printer Tidak Ditemukan';
                    
                    const isConnected = window.PrinterManager.isPrinterReady(role);
                    this.printers[role].status = isConnected ? 'Terhubung' : 'Tidak Terhubung'; 
                };

                this.onForgotten = (event) => {
                    const role = event.detail?.role;
                    if (!role || !this.printers[role]) return;
                    this.printers[role].deviceName = 'Belum Dikonfigurasi';
                    this.printers[role].status = 'Tidak Terhubung';
                };

                window.addEventListener('printer-restored', this.onRestored);
                window.addEventListener('printer-forgotten', this.onForgotten);

                if (this.sources.length > 0) {
                    window.PrinterManager.configureSources(this.sources);
                    this.buildPrinters();
                    this.syncFromManager();
                }

                this.onSourcesUpdated = (event) => {
                    const nextSources = event.detail?.sources;
                    this.sources = Array.isArray(nextSources) ? nextSources : (Array.isArray(window.PRINTER_SOURCES) ? window.PRINTER_SOURCES : []);

                    if (this.sources.length > 0) {
                        window.PrinterManager.configureSources(this.sources);
                        this.buildPrinters();
                        this.syncFromManager();
                    } else {
                        this.printers = {};
                    }
                };

                window.addEventListener('printer-sources-updated', this.onSourcesUpdated);

                // Global listener for Printing (Server -> Client)
                // This triggers the global PrinterManager, so it only needs to be registered once.
                if (!window.__printerGlobalListenerRegistered) {
                    window.__printerGlobalListenerRegistered = true;
                    Livewire.on('doPrintReceipt', (data) => {
                        const orderData = Array.isArray(data) ? data[0] : data;
                        window.PrinterManager.printOrder(orderData);
                    });
                }

                // Instance listeners for Status Updates (Client -> UI)
                // These must be bound to THIS instance to update the UI variables
                this.onDisconnect = (event) => {
                    const device = event.detail?.device;
                    if (!device) return;
                    Object.keys(this.printers).forEach(role => {
                        if (window.PrinterManager.devices[role] === device) {
                            this.printers[role].status = 'Tidak Terhubung';
                        }
                    });
                };

                this.onConnect = (event) => {
                    const role = event.detail?.role;
                    const device = event.detail?.device;
                    if (!role || !this.printers[role]) return;
                    this.printers[role].deviceName = device?.name || this.printers[role].deviceName;
                    this.printers[role].status = 'Terhubung';
                };

                window.addEventListener('printer-disconnected', this.onDisconnect);
                window.addEventListener('printer-connected', this.onConnect);
            },

            destroy() {
                // Cleanup event listeners when component is removed (e.g. navigation)
                window.removeEventListener('printer-disconnected', this.onDisconnect);
                window.removeEventListener('printer-connected', this.onConnect);
                window.removeEventListener('printer-restored', this.onRestored);
                window.removeEventListener('printer-forgotten', this.onForgotten);
                window.removeEventListener('printer-sources-updated', this.onSourcesUpdated);
            },

            buildPrinters() {
                const map = {};
                this.sources.forEach(source => {
                    const role = `source-${source.id}`;
                    const device = window.PrinterManager.devices[role];
                    const connected = window.PrinterManager.isPrinterReady(role);
                    map[role] = {
                        label: source.name,
                        type: source.type,
                        deviceName: device?.name || 'Belum Dikonfigurasi',
                        status: connected ? 'Terhubung' : 'Tidak Terhubung'
                    };
                });
                this.printers = map;
            },

            syncFromManager() {
                Object.keys(this.printers).forEach(role => {
                    const device = window.PrinterManager.devices[role];
                    const connected = window.PrinterManager.isPrinterReady(role);
                    this.printers[role].deviceName = device?.name || 'Belum Dikonfigurasi';
                    this.printers[role].status = connected ? 'Terhubung' : 'Tidak Terhubung';
                });
            },

            async setup(role) {
                const success = await window.PrinterManager.setupPrinter(role);
                if (success) {
                    const device = window.PrinterManager.devices[role];
                    this.printers[role].deviceName = device.name;
                    this.printers[role].status = 'Terhubung';
                }
            },

            forget(role) {
                window.PrinterManager.forgetPrinter(role);
            },

            testPrint(role) {
                const dummyOrder = {
                    store: {
                        name: 'TEST PRINT',
                        address: 'Testing St.',
                        phone: '123'
                    },
                    order: { code: 'TEST-001', transaction_number: 'TEST-001' },
                    items: [{ name: 'Test Item', quantity: 1, price: 1000 }],
                    date: new Date().toLocaleString()
                };

                const config = this.printers[role];
                const type = String(config?.type || '').toLowerCase();
                if (type === 'kasir') {
                    const data = window.ReceiptTemplates.customerCopy(dummyOrder);
                    window.PrintQueue.add({ printerRole: role, data });
                } else {
                    const label = config?.label || 'KITCHEN';
                    const data = window.ReceiptTemplates.kitchenCopy(dummyOrder, label.toUpperCase());
                    window.PrintQueue.add({ printerRole: role, data });
                }
            }
            }));
        }

        if (!window.__posPrintModalLivewireListenerRegistered) {
            window.__posPrintModalLivewireListenerRegistered = true;
            if (window.Livewire?.on) {
                window.Livewire.on('pos-print-modal', (data) => {
                    const payload = data?.detail ?? (Array.isArray(data) ? data[0] : data) ?? data;
                    window.dispatchEvent(new CustomEvent('pos-print-modal', { detail: payload }));
                });
            }
        }

        if (!Alpine.data('posPrintModal')) {
            Alpine.data('posPrintModal', () => ({
                open: false,
                context: null,
                payload: null,
                busy: false,
                reconnecting: {},
                showAllPrinters: false,
                selected: {},
                issues: [],
                statusTick: 0,
                init() {
                    this.onModal = (data) => {
                        const payload = data?.detail?.payload ?? data?.payload ?? (Array.isArray(data) ? data[0]?.payload : null);
                        const context = data?.detail?.context ?? data?.context ?? (Array.isArray(data) ? data[0]?.context : null);
                        if (!payload) return;

                        if (window.PRINTER_DEBUG) {
                            try {
                                console.groupCollapsed('[POS Print] event received: pos-print-modal');
                                console.info('context', context);
                                console.info('payload keys', Object.keys(payload || {}));
                                console.info('order', payload?.order);
                                console.info('printer_sources', payload?.printer_sources);
                                console.info('items_by_source keys', payload?.items_by_source ? Object.keys(payload.items_by_source) : null);
                                console.groupEnd();
                            } catch (e) {
                            }
                        }

                        this.payload = payload;
                        this.context = context;
                        this.open = true;
                        this.busy = false;
                        this.showAllPrinters = false;

                        const selected = {};
                        const suggested = Array.isArray(payload?.suggested_source_ids) ? payload.suggested_source_ids : [];
                        suggested.forEach((id) => {
                            if (!id) return;
                            selected[this.roleFromSourceId(id)] = true;
                        });

                        try {
                            const sources = Array.isArray(payload?.printer_sources) ? payload.printer_sources : (Array.isArray(window.PRINTER_SOURCES) ? window.PRINTER_SOURCES : []);
                            sources.forEach((s) => {
                                const id = Number(s?.id);
                                const type = String(s?.type || '').toLowerCase();
                                if (!id) return;
                                if (type === 'kasir' || type === 'checker') {
                                    selected[this.roleFromSourceId(id)] = true;
                                }
                            });
                        } catch (e) {
                        }

                        if (Object.keys(selected).length === 0 && payload?.default_kasir_source_id) {
                            selected[this.roleFromSourceId(payload.default_kasir_source_id)] = true;
                        }

                        this.selected = selected;
                        this.revalidate();
                    };

                    window.addEventListener('pos-print-modal', this.onModal);

                    this.onIssuesUpdated = () => {
                        if (!this.open) return;
                        this.revalidate();
                    };
                    window.addEventListener('printer-issues-updated', this.onIssuesUpdated);

                    this.onPrinterStatusChanged = () => {
                        if (!this.open) return;
                        this.statusTick = Number(this.statusTick || 0) + 1;
                        this.revalidate();
                    };
                    window.addEventListener('printer-connected', this.onPrinterStatusChanged);
                    window.addEventListener('printer-disconnected', this.onPrinterStatusChanged);
                    window.addEventListener('printer-restored', this.onPrinterStatusChanged);
                    window.addEventListener('printer-forgotten', this.onPrinterStatusChanged);
                },
                destroy() {
                    window.removeEventListener('pos-print-modal', this.onModal);
                    window.removeEventListener('printer-issues-updated', this.onIssuesUpdated);
                    window.removeEventListener('printer-connected', this.onPrinterStatusChanged);
                    window.removeEventListener('printer-disconnected', this.onPrinterStatusChanged);
                    window.removeEventListener('printer-restored', this.onPrinterStatusChanged);
                    window.removeEventListener('printer-forgotten', this.onPrinterStatusChanged);
                },
                close() {
                    this.open = false;
                    this.context = null;
                    this.payload = null;
                    this.busy = false;
                    this.reconnecting = {};
                    this.selected = {};
                    this.issues = [];
                    this.statusTick = Number(this.statusTick || 0) + 1;
                },
                sources() {
                    const list = Array.isArray(this.payload?.printer_sources) ? this.payload.printer_sources : (Array.isArray(window.PRINTER_SOURCES) ? window.PRINTER_SOURCES : []);
                    return list.map((s) => ({
                        id: Number(s.id),
                        name: String(s.name || ''),
                        type: String(s.type || '')
                    }));
                },
                roleFromSourceId(sourceId) {
                    return `source-${sourceId}`;
                },
                itemsForSource(source) {
                    if (!this.payload) return [];
                    const type = String(source?.type || '').toLowerCase();
                    if (type === 'kasir') return Array.isArray(this.payload.items) ? this.payload.items : [];
                    if (type === 'checker') return Array.isArray(this.payload.checker_items) ? this.payload.checker_items : [];
                    const map = this.payload.items_by_source || {};
                    const items = map?.[source.id];
                    return Array.isArray(items) ? items : [];
                },
                visibleSources() {
                    const list = this.sources();
                    if (this.showAllPrinters) return list;
                    return list.filter((s) => {
                        const type = String(s?.type || '').toLowerCase();
                        const count = this.itemsForSource(s).length;
                        if (type === 'dapur') return count > 0;
                        return true;
                    });
                },
                toggle(role) {
                    const next = { ...(this.selected || {}) };
                    next[role] = !next[role];
                    if (!next[role]) delete next[role];
                    this.selected = next;
                    this.revalidate();
                },
                selectedSourceIds() {
                    return Object.keys(this.selected || {})
                        .filter((role) => this.selected[role])
                        .map((role) => Number(String(role).replace('source-', '')))
                        .filter((id) => Number.isFinite(id) && id > 0);
                },
                selectedJobs() {
                    const jobs = [];
                    const selectedIds = this.selectedSourceIds();
                    const sources = this.sources();
                    const byId = {};
                    sources.forEach((s) => { byId[s.id] = s; });

                    selectedIds.forEach((id) => {
                        const source = byId[id];
                        if (!source) return;
                        const items = this.itemsForSource(source);
                        const type = String(source.type || '').toLowerCase();
                        if (type !== 'kasir' && items.length === 0) return;

                        jobs.push({
                            printer_source_id: id,
                            printerRole: this.roleFromSourceId(id),
                            label: source.name,
                            items,
                        });
                    });

                    return jobs;
                },
                kasirSource() {
                    const sources = this.sources();
                    const kasir = sources.find((s) => String(s.type || '').toLowerCase() === 'kasir');
                    return kasir || null;
                },
                kasirJob() {
                    const kasir = this.kasirSource();
                    if (!kasir) return null;
                    return {
                        printer_source_id: kasir.id,
                        printerRole: this.roleFromSourceId(kasir.id),
                        label: kasir.name,
                        items: this.itemsForSource(kasir),
                    };
                },
                hasKitchenSelected() {
                    const selectedIds = this.selectedSourceIds();
                    const sources = this.sources();
                    const byId = {};
                    sources.forEach((s) => { byId[s.id] = s; });
                    return selectedIds.some((id) => {
                        const s = byId[id];
                        const type = String(s?.type || '').toLowerCase();
                        return s && type === 'dapur' && this.itemsForSource(s).length > 0;
                    });
                },
                printerStatus(source) {
                    this.statusTick;
                    const role = this.roleFromSourceId(source.id);
                    const savedId = localStorage.getItem(`printer_device_${role}`);
                    if (!savedId) return { key: 'missing_config', label: 'Belum Setup' };
                    if (!window.PrinterManager?.devices?.[role]) return { key: 'permission_lost', label: 'Izin Hilang' };
                    if (window.PrinterManager?.isPrinterReady?.(role)) return { key: 'connected', label: 'Terhubung' };
                    return { key: 'offline', label: 'Offline' };
                },
                statusClass(statusKey) {
                    if (statusKey === 'connected') return 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400';
                    if (statusKey === 'missing_config' || statusKey === 'permission_lost') return 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400';
                    return 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400';
                },
                isReconnecting(role) {
                    return !!this.reconnecting?.[role];
                },
                async reconnect(role) {
                    if (!role || this.isReconnecting(role)) return;
                    this.reconnecting = { ...(this.reconnecting || {}), [role]: true };
                    try {
                        await window.PrinterManager?.reconnectRole(role);
                    } finally {
                        const next = { ...(this.reconnecting || {}) };
                        delete next[role];
                        this.reconnecting = next;
                        this.revalidate();
                    }
                },
                unassignedNames() {
                    const items = Array.isArray(this.payload?.unassigned_items) ? this.payload.unassigned_items : [];
                    const names = items.map((i) => String(i?.product?.name || i?.name || '')).filter(Boolean);
                    const uniq = {};
                    names.forEach((n) => { uniq[n] = true; });
                    return Object.keys(uniq);
                },
                revalidate() {
                    const issues = [];
                    const jobs = this.selectedJobs();
                    const sources = this.sources();
                    const byId = {};
                    sources.forEach((s) => { byId[s.id] = s; });

                    if (jobs.length === 0) {
                        issues.push({ key: 'no_selection', type: 'no_selection', message: 'Pilih minimal 1 printer untuk mencetak.' });
                    }

                    jobs.forEach((job) => {
                        const source = byId[job.printer_source_id];
                        const role = this.roleFromSourceId(job.printer_source_id);
                        const label = source?.name || role;
                        const savedId = localStorage.getItem(`printer_device_${role}`);

                        if (!savedId) {
                            issues.push({ key: `${role}_missing_config`, role, type: 'missing_config', label, message: 'Printer belum disetup.' });
                            return;
                        }

                        if (!window.PrinterManager?.devices?.[role]) {
                            issues.push({ key: `${role}_permission_lost`, role, type: 'permission_lost', label, message: 'Izin perangkat hilang. Setup ulang diperlukan.' });
                            return;
                        }

                        if (!window.PrinterManager?.isPrinterReady?.(role)) {
                            issues.push({ key: `${role}_offline`, role, type: 'offline', label, message: 'Printer belum Terhubung.' });
                        }
                    });

                    const unassigned = this.unassignedNames();
                    if (this.hasKitchenSelected() && unassigned.length > 0) {
                        issues.push({
                            key: 'unassigned_items',
                            type: 'unassigned_items',
                            label: 'Pemetaan Produk',
                            message: `${unassigned.length} produk belum dipetakan ke printer.`,
                        });
                    }

                    this.issues = issues;
                },
                canPrintSelected() {
                    return !this.busy && this.blockingIssues().length === 0;
                },
                kasirIssues() {
                    const kasir = this.kasirSource();
                    if (!kasir) return [{ key: 'no_kasir', type: 'no_kasir', message: 'Printer kasir belum dibuat.' }];
                    const role = this.roleFromSourceId(kasir.id);
                    const label = kasir.name || role;
                    const savedId = localStorage.getItem(`printer_device_${role}`);

                    if (!savedId) return [{ key: `${role}_missing_config`, role, type: 'missing_config', label, message: 'Printer kasir belum disetup.' }];
                    if (!window.PrinterManager?.devices?.[role]) return [{ key: `${role}_permission_lost`, role, type: 'permission_lost', label, message: 'Izin perangkat hilang. Setup ulang diperlukan.' }];
                    if (!window.PrinterManager?.isPrinterReady?.(role)) return [{ key: `${role}_offline`, role, type: 'offline', label, message: 'Printer kasir belum Terhubung.' }];
                    return [];
                },
                canPrintKasirOnly() {
                    const issues = this.kasirIssues();
                    return !this.busy && Array.isArray(issues) && issues.length === 0;
                },
                badgeClass(issue) {
                    const type = String(issue?.type || '');
                    if (type === 'missing_config' || type === 'permission_lost' || type === 'unassigned_items' || type === 'no_kasir') {
                        return 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400';
                    }
                    return 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400';
                },
                badgeLabel(issue) {
                    const type = String(issue?.type || '');
                    if (type === 'missing_config') return 'Belum Setup';
                    if (type === 'permission_lost') return 'Izin Hilang';
                    if (type === 'unassigned_items') return 'Belum Dipetakan';
                    if (type === 'no_selection') return 'Pilih Printer';
                    if (type === 'no_kasir') return 'Kasir';
                    return 'Offline';
                },
                blockingIssues() {
                    return (Array.isArray(this.issues) ? this.issues : []).filter((issue) => {
                        const type = String(issue?.type || '');
                        return type !== 'unassigned_items';
                    });
                },
                async printSelected() {
                    if (this.busy) return;
                    this.revalidate();
                    if (this.blockingIssues().length > 0) {
                            if (window.PRINTER_DEBUG) {
                                try {
                                    console.groupCollapsed('[POS Print] blocked by issues');
                                    console.info('selectedSourceIds', this.selectedSourceIds());
                                    console.info('selectedJobs', this.selectedJobs());
                                    console.table(Array.isArray(this.issues) ? this.issues : []);
                                    console.info('issuesRaw', this.issues);
                                    console.groupEnd();
                                } catch (e) {
                                }
                            }
                            return;
                    }

                    this.busy = true;
                    try {
                        const orderData = {
                            store: this.payload.store,
                            order: this.payload.order,
                            customer_name: this.payload.customer_name,
                            name_kasir: this.payload.name_kasir,
                            table_number: this.payload.table_number,
                            items: this.payload.items,
                            print_jobs: this.selectedJobs(),
                        };
                        if (window.PRINTER_DEBUG) {
                            try {
                                console.groupCollapsed('[POS Print] printSelected');
                                console.info('order', orderData?.order);
                                console.info('print_jobs', orderData?.print_jobs);
                                console.groupEnd();
                            } catch (e) {
                            }
                        }

                        if (!window.PrinterManager?.printOrder) {
                            console.error('[POS Print] PrinterManager tidak tersedia');
                            return;
                        }

                        try {
                            window.PrinterManager.printOrder(orderData);
                        } catch (e) {
                            console.error('[POS Print] printOrder error', e);
                            return;
                        }
                        this.close();
                    } finally {
                        this.busy = false;
                    }
                },
                async printKasirOnly() {
                    if (this.busy) return;
                    if (this.kasirIssues().length > 0) {
                            if (window.PRINTER_DEBUG) console.warn('[POS Print] kasir-only blocked', { issues: this.kasirIssues() });
                            return;
                    }
                    this.busy = true;
                    try {
                        const job = this.kasirJob();
                        if (!job) return;

                        const orderData = {
                            store: this.payload.store,
                            order: this.payload.order,
                            customer_name: this.payload.customer_name,
                            name_kasir: this.payload.name_kasir,
                            table_number: this.payload.table_number,
                            items: this.payload.items,
                            print_jobs: [job],
                        };
                        if (window.PRINTER_DEBUG) {
                            try {
                                console.groupCollapsed('[POS Print] printKasirOnly');
                                console.info('order', orderData?.order);
                                console.info('print_jobs', orderData?.print_jobs);
                                console.groupEnd();
                            } catch (e) {
                            }
                        }

                        if (!window.PrinterManager?.printOrder) {
                            console.error('[POS Print] PrinterManager tidak tersedia');
                            return;
                        }

                        try {
                            window.PrinterManager.printOrder(orderData);
                        } catch (e) {
                            console.error('[POS Print] printOrder error', e);
                            return;
                        }
                        this.close();
                    } finally {
                        this.busy = false;
                    }
                },
            }));
        }
    };

    // Ensure registration happens whether Alpine is already initialized or not
    if (window.Alpine) {
        registerPrinterSystem();
    } else {
        document.addEventListener('alpine:init', registerPrinterSystem);
    }
})();
