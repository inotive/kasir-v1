/**
 * PrinterManager.js
 * Orchestrates multiple printers and handles logic.
 */

if (!window.PrinterManager) {
    class PrinterManager {
        constructor() {
            this.devices = {};
            this.connections = {};
            this.roles = [];
            this.sources = [];
            this._restoreInProgress = false;
            this._reconnectAttempts = {};
            this._issues = [];
            this._sourcesKey = '';
            this._deviceConnections = {};

            window.addEventListener('printer-disconnected', (event) => {
                const device = event.detail?.device;
                if (!device) return;

                Object.keys(this.devices).forEach((role) => {
                    const roleDevice = this.devices[role];
                    if (roleDevice && roleDevice.id === device.id) {
                        this.connections[role] = null;
                        window.dispatchEvent(new CustomEvent('printer-issues-updated', {
                            detail: {
                                issues: this._mergeIssues([{
                                    role,
                                    sourceName: this.sources.find(s => s.role === role)?.name || role,
                                    type: 'disconnected',
                                    message: 'Printer terputus.'
                                }])
                            }
                        }));
                    }
                });

                if (device.id && this._deviceConnections[device.id]) {
                    this._deviceConnections[device.id] = null;
                }
            });
        }

        configureSources(sources) {
            const list = Array.isArray(sources) ? sources : [];
            const nextKey = JSON.stringify(list.map(s => [s.id, s.name, s.type]));
            if (nextKey === this._sourcesKey) {
                return;
            }
            this._reconnectAttempts = {};
            this._sourcesKey = nextKey;

            this.sources = list.map(source => ({
                id: source.id,
                name: source.name,
                type: source.type,
                role: `source-${source.id}`
            }));

            const newDevices = {};
            const newConnections = {};
            this.sources.forEach(source => {
                newDevices[source.role] = this.devices[source.role] || null;
                newConnections[source.role] = this.connections[source.role] || null;
            });
            this.devices = newDevices;
            this.connections = newConnections;
            this.roles = this.sources.map(source => source.role);
            
            // Try to load saved devices from localStorage
            if (this.roles.length > 0) {
                this.loadSavedDevices();
            }
        }

        async loadSavedDevices() {
            if (this._restoreInProgress) {
                return;
            }
            this._restoreInProgress = true;
            const savedMap = {};
            const issues = [];
            
            if (window.PRINTER_DEBUG) console.log("Loading saved devices and auto-reconnecting...");
            
            this.roles.forEach(role => {
                const id = localStorage.getItem(`printer_device_${role}`);
                if (id) {
                    savedMap[role] = id;
                    if (window.PRINTER_DEBUG) console.log(`Found saved ID for ${role}: ${id}`);
                } else {
                    // Collect issue: No saved printer for this role
                    issues.push({
                        role: role,
                        sourceName: this.sources.find(s => s.role === role)?.name || role,
                        type: 'missing_config',
                        message: 'Printer belum dikonfigurasi.'
                    });
                }
            });

            try {
                // If we have saved devices, we need permitted devices list
                let permittedDevices = [];
                if (Object.keys(savedMap).length > 0) {
                     permittedDevices = await window.BluetoothService.getDevices();
                     if (window.PRINTER_DEBUG) console.log("Permitted devices found:", permittedDevices.map(d => `${d.name} (${d.id})`));
                }

                const pendingByDeviceId = {};

                for (const role of this.roles) {
                    if (this.isPrinterReady(role)) {
                        window.dispatchEvent(new CustomEvent('printer-connected', { detail: { role, device: this.devices[role] } }));
                        continue;
                    }

                    const savedId = savedMap[role];
                    if (!savedId) {
                        continue;
                    }

                    const device = permittedDevices.find(d => d.id === savedId);
                    if (!device) {
                        console.warn(`Saved device ID ${savedId} for ${role} not found in permitted devices.`);
                        issues.push({
                            role: role,
                            sourceName: this.sources.find(s => s.role === role)?.name || role,
                            type: 'permission_lost',
                            message: 'Izin akses perangkat hilang/expired. Perlu pairing ulang.'
                        });
                        continue;
                    }

                    this.devices[role] = device;
                    window.dispatchEvent(new CustomEvent('printer-restored', { detail: { role, device } }));

                    const deviceId = device.id || role;
                    const existingServer = this._deviceConnections[deviceId];
                    if (existingServer && existingServer.connected) {
                        this.connections[role] = existingServer;
                        window.dispatchEvent(new CustomEvent('printer-connected', { detail: { role, device } }));
                        continue;
                    }

                    if (!pendingByDeviceId[deviceId]) {
                        pendingByDeviceId[deviceId] = { device, roles: [] };
                    }
                    pendingByDeviceId[deviceId].roles.push(role);
                }

                for (const deviceId of Object.keys(pendingByDeviceId)) {
                    const entry = pendingByDeviceId[deviceId];
                    const device = entry.device;
                    const roles = entry.roles;

                    if (roles.length <= 0) {
                        continue;
                    }

                    if (window.PRINTER_DEBUG) console.log(`Attempting auto-connect for ${device.name} (${roles.join(', ')})`);
                    try {
                        this._reconnectAttempts[deviceId] = (this._reconnectAttempts[deviceId] || 0) + 1;
                        const server = await window.BluetoothService.connect(device, 1);
                        this._deviceConnections[deviceId] = server;
                        roles.forEach((role) => {
                            this.connections[role] = server;
                            window.dispatchEvent(new CustomEvent('printer-connected', { detail: { role, device } }));
                        });
                    } catch (connErr) {
                        roles.forEach((role) => {
                            issues.push({
                                role: role,
                                sourceName: this.sources.find(s => s.role === role)?.name || role,
                                type: 'connection_failed',
                                message: 'Gagal menyambung otomatis. (Pastikan printer nyala/dekat)',
                                device: device
                            });
                        });
                    }
                }

                window.dispatchEvent(new CustomEvent('printer-issues-updated', { detail: { issues: this._mergeIssues(issues) } }));

            } catch (error) {
                console.error("Error loading saved devices:", error);
            } finally {
                this._restoreInProgress = false;
            }
        }

        /**
         * Helper to emit loading state
         * @param {Boolean} show 
         * @param {String} message 
         */
        emitLoading(show, message = '') {
            if (show) {
                window.dispatchEvent(new CustomEvent('printer-loading-start', { detail: { message } }));
            } else {
                window.dispatchEvent(new CustomEvent('printer-loading-end'));
            }
        }

        /**
         * Validate all printers before batch printing.
         * 1. Check if all printers are configured (localStorage).
         * 2. Check connection status and attempt reconnect if needed.
         * @returns {Promise<{success: boolean, message?: string}>}
         */
        async validatePrintersForBatchPrint() {
            // 1. Check Configuration Existence First
            for (const source of this.sources) {
                const role = source.role;
                const savedId = localStorage.getItem(`printer_device_${role}`);
                if (!savedId) {
                    return { 
                        success: false, 
                        message: `Printer "${source.name}" (${source.type}) belum dikonfigurasi. Silakan setup printer terlebih dahulu.` 
                    };
                }
            }

            // 2. Check Connection & Auto-Reconnect
            this.emitLoading(true, 'Memeriksa koneksi semua printer...');
            
            try {
                // Pre-load permitted devices if we need to reconnect any
                // This avoids multiple permission popups if browser supports silent reconnect
                // but usually we rely on ensureConnection's logic.
                
                for (const source of this.sources) {
                    const role = source.role;
                    
                    if (this.isPrinterReady(role)) {
                        continue;
                    }

                    // If not ready, try to ensure connection
                    // We must have the device object loaded from localStorage (done in loadSavedDevices)
                    // If device object is missing but localStorage has ID, loadSavedDevices failed or hasn't run fully.
                    // But ensureConnection checks this.devices[role].

                    // Wait... if loadSavedDevices failed to find device in permitted list, this.devices[role] is null.
                    // So we check this.devices[role] here too.
                    if (!this.devices[role]) {
                         return { 
                            success: false, 
                            message: `Printer "${source.name}" tidak dapat ditemukan atau izin akses hilang. Silakan setup ulang.` 
                        };
                    }

                    this.emitLoading(true, `Menyambungkan printer ${source.name}...`);
                    try {
                        await this.ensureConnection(role);
                    } catch (e) {
                        console.error(`Validation failed for ${role}:`, e);
                        return {
                            success: false,
                            message: `Gagal menyambung ke printer "${source.name}". Pastikan printer nyala dan dalam jangkauan.`
                        };
                    }
                }
            } finally {
                this.emitLoading(false);
            }

            return { success: true };
        }

        /**
         * Initiate connection for a specific role.
         * Must be called via user gesture.
         * @param {String} role 
         * @param {Boolean} forcePicker
         */
        async setupPrinter(role, forcePicker = false) {
            if (!this.roles.includes(role)) {
                console.error("Invalid role:", role);
                return false;
            }

            this.emitLoading(true, 'Mencari printer...');

            try {
                let device = this.devices[role];

                // If we have a saved device and not forcing picker, try to connect to it
                if (device && !forcePicker) {
                    if (window.PRINTER_DEBUG) console.log(`Attempting to reconnect to saved device for ${role}...`);
                    this.emitLoading(true, 'Menyambungkan kembali ke printer tersimpan...');
                    try {
                        const deviceId = device.id || role;
                        const server = await window.BluetoothService.connect(device, 1);
                        this._deviceConnections[deviceId] = server;
                        this.connections[role] = server;
                        if (window.PRINTER_DEBUG) console.log(`Reconnected to ${role}: ${device.name}`);
                        window.dispatchEvent(new CustomEvent('printer-connected', { detail: { role, device } }));
                        window.dispatchEvent(new CustomEvent('printer-issues-updated', { detail: { issues: this._mergeIssues([]) } }));
                        return true;
                    } catch (e) {
                        console.warn(`Failed to reconnect to saved device for ${role}. Fallback to picker.`, e);
                        // Fallback will proceed below
                        device = null;
                    }
                }

                // If no device or fallback needed
                if (!device) {
                    this.emitLoading(true, 'Mencari perangkat Bluetooth...');
                    device = await window.BluetoothService.requestDevice();
                    this.devices[role] = device;
                }
                
                // Attempt immediate connection to verify
                this.emitLoading(true, 'Menyambungkan...');
                const deviceId = device.id || role;
                const server = await window.BluetoothService.connect(device, 3);
                this._deviceConnections[deviceId] = server;
                this.connections[role] = server;

                // Save to localStorage
                if (device && device.id) {
                    localStorage.setItem(`printer_device_${role}`, device.id);
                }

                if (window.PRINTER_DEBUG) console.log(`Printer assigned to ${role}: ${device.name}`);
                window.dispatchEvent(new CustomEvent('printer-connected', { detail: { role, device } }));
                window.dispatchEvent(new CustomEvent('printer-issues-updated', { detail: { issues: this._mergeIssues([]) } }));
                return true;
            } catch (error) {
                console.error(`Failed to setup ${role}:`, error);
                return false;
            } finally {
                this.emitLoading(false);
            }
        }

        /**
         * Forget a printer for a role
         * @param {String} role 
         */
        forgetPrinter(role) {
            const currentDevice = this.devices[role];
            let isShared = false;

            // Check if this device is used by any other role
            if (currentDevice && currentDevice.id) {
                for (const otherRole of this.roles) {
                    if (otherRole !== role && this.devices[otherRole] && this.devices[otherRole].id === currentDevice.id) {
                        isShared = true;
                        if (window.PRINTER_DEBUG) console.log(`Device ${currentDevice.name} is shared with ${otherRole}. Will not disconnect physical connection.`);
                        break;
                    }
                }
            }

            if (this.connections[role]) {
                try {
                    // Only disconnect if NOT shared with other roles
                    if (!isShared && this.connections[role].disconnect) {
                        if (window.PRINTER_DEBUG) console.log(`Disconnecting ${role} (not shared)...`);
                        this.connections[role].disconnect();
                    }
                } catch(e) { console.warn("Disconnect error:", e); }
                this.connections[role] = null;
            }
            
            this.devices[role] = null;
            localStorage.removeItem(`printer_device_${role}`);
            window.dispatchEvent(new CustomEvent('printer-forgotten', { detail: { role } }));
            window.dispatchEvent(new CustomEvent('printer-issues-updated', {
                detail: {
                    issues: this._mergeIssues([{
                        role,
                        sourceName: this.sources.find(s => s.role === role)?.name || role,
                        type: 'missing_config',
                        message: 'Printer belum dikonfigurasi.'
                    }])
                }
            }));
        }


        /**
         * Check if a printer is ready
         * @param {String} role 
         */
        isPrinterReady(role) {
            return this.devices[role] && this.connections[role] && this.connections[role].connected;
        }

        /**
         * Reconnect if disconnected
         * @param {String} role 
         */
        async ensureConnection(role) {
            if (!this.devices[role]) {
                throw new Error(`No printer assigned for ${role}`);
            }

            const device = this.devices[role];
            const deviceId = device.id || role;

            const shared = this._deviceConnections[deviceId];
            if (shared && shared.connected) {
                this.connections[role] = shared;
                return shared;
            }

            if (!this.connections[role] || !this.connections[role].connected) {
                if (window.PRINTER_DEBUG) console.log(`Reconnecting ${role}...`);
                this.emitLoading(true, 'Menyambungkan kembali printer...');
                try {
                    const attempts = this._reconnectAttempts[deviceId] || 0;
                    if (attempts >= 1) {
                        const err = new Error('Auto reconnect dibatasi 1x per reload halaman.');
                        err.code = 'AUTO_RECONNECT_LIMIT';
                        throw err;
                    }
                    this._reconnectAttempts[deviceId] = attempts + 1;
                    this.connections[role] = await window.BluetoothService.connect(device, 1);
                    this._deviceConnections[deviceId] = this.connections[role];
                    window.dispatchEvent(new CustomEvent('printer-connected', { detail: { role, device } }));
                    window.dispatchEvent(new CustomEvent('printer-issues-updated', { detail: { issues: this._mergeIssues([]) } }));
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('printer-issues-updated', {
                        detail: {
                            issues: this._mergeIssues([{
                                role,
                                sourceName: this.sources.find(s => s.role === role)?.name || role,
                                type: 'connection_failed',
                                message: 'Gagal menyambung. Pastikan printer nyala dan dekat.'
                            }])
                        }
                    }));
                    throw e;
                } finally {
                    this.emitLoading(false);
                }
            }
            
            return this.connections[role];
        }

        async reconnectRole(role) {
            if (!this.devices[role]) {
                return false;
            }

            this.emitLoading(true, 'Menyambungkan printer...');
            try {
                const device = this.devices[role];
                const deviceId = device.id || role;

                this._reconnectAttempts[deviceId] = 0;
                const server = await window.BluetoothService.connect(device, 1);
                this._deviceConnections[deviceId] = server;

                this.roles.forEach((r) => {
                    const d = this.devices[r];
                    if (d && d.id === device.id) {
                        this.connections[r] = server;
                        window.dispatchEvent(new CustomEvent('printer-connected', { detail: { role: r, device: d } }));
                    }
                });

                window.dispatchEvent(new CustomEvent('printer-issues-updated', { detail: { issues: this._mergeIssues([]) } }));
                return true;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('printer-issues-updated', {
                    detail: {
                        issues: this._mergeIssues([{
                            role,
                            sourceName: this.sources.find(s => s.role === role)?.name || role,
                            type: 'connection_failed',
                            message: 'Gagal menyambung. Pastikan printer nyala dan dekat.'
                        }])
                    }
                }));
                return false;
            } finally {
                this.emitLoading(false);
            }
        }

        _mergeIssues(nextIssues) {
            const issues = Array.isArray(nextIssues) ? nextIssues : [];
            const byRole = {};

            [...(this._issues || []), ...issues].forEach((issue) => {
                if (!issue || !issue.role) return;
                byRole[issue.role] = issue;
            });

            this.roles.forEach((role) => {
                if (this.isPrinterReady(role)) {
                    delete byRole[role];
                }
            });

            this._issues = Object.values(byRole);
            return this._issues;
        }

        /**
         * Entry point for printing an order
         * @param {Object} orderData 
         */
        printOrder(orderData) {
            if (window.PRINTER_DEBUG) {
                try {
                    console.groupCollapsed('[PrinterManager] printOrder');
                    console.info('order', orderData?.order);
                    console.info('jobs', orderData?.print_jobs);
                    console.groupEnd();
                } catch (e) {
                }
            }

            const sourceRoleMap = this.sources.reduce((acc, source) => {
                acc[source.id] = source.role;
                return acc;
            }, {});

            if (Array.isArray(orderData?.print_jobs) && orderData.print_jobs.length > 0) {
                orderData.print_jobs.forEach((job) => {
                    const role = job?.printerRole || job?.printer_role || null;
                    const sourceId = job?.printer_source_id ? Number(job.printer_source_id) : null;
                    const resolvedRole = role || (sourceId ? sourceRoleMap[sourceId] : null);
                    if (!resolvedRole) {
                        console.error('[PrinterManager] job ignored: role not resolved', job);
                        return;
                    }

                    const source = this.sources.find(s => s.role === resolvedRole) || null;
                    const sourceType = String(job?.template || source?.type || '').toLowerCase();
                    const jobItems = Array.isArray(job?.items) ? job.items : (Array.isArray(orderData?.items) ? orderData.items : []);

                    if (sourceType === 'kasir') {
                        const customerData = window.ReceiptTemplates.customerCopy({
                            ...orderData,
                            items: jobItems
                        });
                        if (window.PRINTER_DEBUG) console.info('[PrinterManager] enqueue kasir', { role: resolvedRole, items: jobItems.length });
                        window.PrintQueue.add({ printerRole: resolvedRole, data: customerData });
                        return;
                    }

                    const label = String(job?.label || source?.name || 'DAPUR');
                    const kitchenData = window.ReceiptTemplates.kitchenCopy({
                        ...orderData,
                        items: jobItems
                    }, label);
                    if (window.PRINTER_DEBUG) console.info('[PrinterManager] enqueue kitchen', { role: resolvedRole, label, items: jobItems.length });
                    window.PrintQueue.add({ printerRole: resolvedRole, data: kitchenData });
                });

                return;
            }
        }

        /**
         * Called by PrintQueue to execute the actual print
         * @param {Object} job 
         */
        async processJob(job) {
            const { printerRole, data } = job;
            if (window.PRINTER_DEBUG) {
                try {
                    console.info('[PrinterManager] processJob', {
                        printerRole,
                        bytes: typeof data === 'string' ? data.length : null,
                    });
                } catch (e) {
                }
            }

            const savedId = localStorage.getItem(`printer_device_${printerRole}`);
            if (!savedId) {
                const err = new Error(`Printer "${printerRole}" belum disetup.`);
                err.code = 'MISSING_CONFIG';
                window.dispatchEvent(new CustomEvent('printer-issues-updated', {
                    detail: {
                        issues: this._mergeIssues([{
                            role: printerRole,
                            sourceName: this.sources.find(s => s.role === printerRole)?.name || printerRole,
                            type: 'missing_config',
                            message: 'Printer belum dikonfigurasi.'
                        }])
                    }
                }));
                throw err;
            }

            if (!this.devices[printerRole]) {
                const err = new Error(`Izin akses printer "${printerRole}" hilang. Setup ulang diperlukan.`);
                err.code = 'PERMISSION_LOST';
                window.dispatchEvent(new CustomEvent('printer-issues-updated', {
                    detail: {
                        issues: this._mergeIssues([{
                            role: printerRole,
                            sourceName: this.sources.find(s => s.role === printerRole)?.name || printerRole,
                            type: 'permission_lost',
                            message: 'Izin akses perangkat hilang/expired. Perlu pairing ulang.'
                        }])
                    }
                }));
                throw err;
            }

            this.emitLoading(true, 'Sedang mencetak...');

            try {
                const server = await this.ensureConnection(printerRole);
                await window.BluetoothService.print(server, data);
                if (window.PRINTER_DEBUG) console.info('[PrinterManager] printed', { printerRole });
            } catch (error) {
                console.error('[PrinterManager] print failed', { printerRole, error });
                throw error; // Re-throw for Queue retry logic
            } finally {
                this.emitLoading(false);
            }
        }
    }

    window.PrinterManager = new PrinterManager();
}
