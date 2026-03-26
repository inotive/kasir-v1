/**
 * ReceiptTemplates.js
 * Generates ESC/POS byte arrays for different receipt types.
 */

if (!window.ReceiptTemplates) {

    const ESC = '\x1B';
    const GS = '\x1D';

    const COMMANDS = {
        RESET: '\x1B\x40',
        ALIGN_LEFT: '\x1B\x61\x00',
        ALIGN_CENTER: '\x1B\x61\x01',
        ALIGN_RIGHT: '\x1B\x61\x02',
        BOLD_ON: '\x1B\x45\x01',
        BOLD_OFF: '\x1B\x45\x00',
        TEXT_NORMAL: '\x1B\x21\x00',
        TEXT_DOUBLE_HEIGHT: '\x1B\x21\x10',
        TEXT_DOUBLE_WIDTH: '\x1B\x21\x20',
        TEXT_QUAD: '\x1B\x21\x30',
        CUT_PAPER: '\x1D\x56\x41\x00' // GS V A 0 (Full cut)
    };

    class ReceiptTemplates {
        constructor() {
            this.encoder = new TextEncoder();
            this._logoRasterCache = new Map();
        }

        formatRibuan(number) {
            return number.toLocaleString("id-ID");
        }

        formatRow(name, qty, price) {
            const nameWidth = 16;
            const qtyWidth = 6;
            const priceWidth = 10;
            
            // Simple word wrap simulation or truncation
            let nameLines = name.match(new RegExp('.{1,' + nameWidth + '}', 'g')) || [];
            let output = '';

            for (let i = 0; i < nameLines.length - 1; i++) {
                output += nameLines[i].padEnd(32) + "\n";
            }

            let lastLine = (nameLines[nameLines.length - 1] || '').padEnd(nameWidth);
            const qtyStr = qty.toString().padStart(qtyWidth);
            const priceStr = price.toString().padStart(priceWidth);
            output += lastLine + qtyStr + priceStr;

            return output;
        }

        formatItem(name, qty, price) {
            const width = 32;
            const total = qty * price;
            const totalStr = this.formatRibuan(total);
            const qtyPriceStr = `${qty} x Rp ${this.formatRibuan(price)}`;
            
            let output = '';
            
            // Name lines
            let currentName = name;
            while (currentName.length > width) {
                output += currentName.substring(0, width) + "\n";
                currentName = currentName.substring(width);
            }
            if (currentName.length > 0) {
                output += currentName + "\n";
            }
            
            // Price line: Qty x Price ...... Total
            let spaceLen = width - qtyPriceStr.length - totalStr.length;
            
            if (qtyPriceStr.length + totalStr.length > width) {
                 // Fallback if too long
                 output += qtyPriceStr + "\n";
                 output += totalStr.padStart(width);
            } else {
                 output += qtyPriceStr + " ".repeat(spaceLen) + totalStr;
            }
            
            return output;
        }

        /**
         * Generate Customer Receipt
         * @param {Object} data 
         */
        async customerCopy(data) {
            const chunks = [];
            chunks.push(this.encoder.encode(COMMANDS.RESET + COMMANDS.ALIGN_CENTER));

            const printLogoFlag = data?.store?.cashier_receipt_print_logo;
            const shouldPrintLogo = printLogoFlag === undefined || printLogoFlag === null ? true : !!printLogoFlag;
            if (shouldPrintLogo) {
                const logoUrl = data?.store?.logo_url ? String(data.store.logo_url).trim() : '';
                if (logoUrl !== '') {
                    const logoBytes = await this._buildLogoRasterBytes(logoUrl);
                    if (logoBytes) {
                        chunks.push(logoBytes);
                    }
                }
            }

            let receipt = '';

            receipt += COMMANDS.ALIGN_CENTER;
            receipt += COMMANDS.TEXT_DOUBLE_HEIGHT;
            receipt += COMMANDS.BOLD_ON;
            receipt += (data.store?.name || 'RESTAURANT') + "\n";
            receipt += COMMANDS.BOLD_OFF;
            receipt += COMMANDS.TEXT_NORMAL;
            receipt += (data.store?.address || '-') + "\n";
            receipt += "Telp: " + (data.store?.phone || '-') + "\n";
            receipt += "================================\n";
            
            // Info
            receipt += COMMANDS.ALIGN_LEFT;
            receipt += "Kode Trx: " + (data.order?.code || data.order?.transaction_number || '-') + "\n";
            receipt += "Pelanggan: " + (data.customer_name || 'Guest') + "\n";
            receipt += "Kasir : " + (data.name_kasir || 'Kasir') + "\n";
            
            let tableInfo = 'Take Away';
            if (data.order?.order_type === 'dine_in') {
                tableInfo = "Meja " + (data.table_number || data.order?.dining_table?.table_number || '-');
            }
            receipt += "Tipe  : " + tableInfo + "\n";

            receipt += "Tgl   : " + (data.date || new Date().toLocaleString()) + "\n";
            receipt += "================================\n";

            let total = 0;
            if (data.items && Array.isArray(data.items)) {
                data.items.forEach(item => {
                    const productName = item.product?.name || item.name || 'Item';
                    const variantName = item.variant_name || item.product_variant?.name || '';
                    const fullName = variantName ? `${productName} (${variantName})` : productName;
                    const qty = item.quantity || 0;
                    const price = item.price || 0;
                    
                    receipt += this.formatItem(fullName, qty, price) + "\n";
                    total += qty * price;
                });
            }

            // Footer Totals
            receipt += "--------------------------------\n";
            
            let subtotal = total; // Default to calculated items total
            if (data.order?.subtotal) {
                subtotal = parseInt(data.order.subtotal);
            }
            
            receipt += this.formatRow("Subtotal", "", this.formatRibuan(subtotal)) + "\n";
            
            const voucherDiscount = parseInt(data.order?.voucher_discount_amount || 0);
            if (voucherDiscount > 0) {
                const voucherCode = (data.order?.voucher_code || '').toString().trim();
                const voucherLabel = voucherCode ? `Voucher ${voucherCode}` : 'Voucher';
                receipt += this.formatRow(voucherLabel, "", this.formatRibuan(-Math.abs(voucherDiscount))) + "\n";
            }
            
            const manualDiscount = parseInt(data.order?.manual_discount_amount || 0);
            if (manualDiscount > 0) {
                receipt += this.formatRow("Diskon Manual", "", this.formatRibuan(-Math.abs(manualDiscount))) + "\n";
            }
            
            const pointDiscount = parseInt(data.order?.point_discount_amount || 0);
            const pointsRedeemed = parseInt(data.order?.points_redeemed || 0);
            if (pointDiscount > 0) {
                receipt += this.formatRow("Poin", pointsRedeemed > 0 ? pointsRedeemed : "", this.formatRibuan(-Math.abs(pointDiscount))) + "\n";
            }

            if (data.order?.tax_amount && data.order.tax_amount > 0) {
                let taxLabel = "PB1";
                if (data.order?.tax_percentage) {
                    taxLabel += ` (${data.order.tax_percentage}%)`;
                }
                receipt += this.formatRow(taxLabel, "", this.formatRibuan(data.order.tax_amount)) + "\n";
            }

            if (data.order?.rounding_amount && data.order.rounding_amount != 0) {
                receipt += this.formatRow("Pembulatan", "", this.formatRibuan(data.order.rounding_amount)) + "\n";
            }

            let finalTotal = total;
            if (data.order?.total) {
                finalTotal = parseInt(data.order.total);
            } else {
                finalTotal = subtotal + (parseInt(data.order?.tax_amount) || 0);
            }

            receipt += this.formatRow("Total", "", this.formatRibuan(finalTotal)) + "\n";

            if (data.order?.cash_received) {
                receipt += this.formatRow("Bayar", "", this.formatRibuan(data.order.cash_received)) + "\n";
            }
            if (data.order?.change) {
                receipt += this.formatRow("Kembali", "", this.formatRibuan(data.order.change)) + "\n";
            }
            
            const pointsEarned = parseInt(data.order?.points_earned || 0);
            if (pointsEarned > 0) {
                receipt += "Poin Dapat: " + pointsEarned + "\n";
            }
            
            receipt += "================================\n";
            receipt += COMMANDS.ALIGN_CENTER;
            receipt += "Terima Kasih!\n";
            receipt += "Silahkan datang kembali\n";
            receipt += "\n\n";
            receipt += COMMANDS.CUT_PAPER;

            chunks.push(this.encoder.encode(receipt));
            return this._concatBytes(chunks);
        }

        _concatBytes(chunks) {
            const arrays = Array.isArray(chunks) ? chunks.filter(Boolean) : [];
            const totalLength = arrays.reduce((sum, a) => sum + (a?.length || 0), 0);
            const out = new Uint8Array(totalLength);
            let offset = 0;
            arrays.forEach((a) => {
                out.set(a, offset);
                offset += a.length;
            });
            return out;
        }

        async _buildLogoRasterBytes(url) {
            const resolvedUrl = this._resolveImageUrl(url);
            if (!resolvedUrl) return null;

            const cached = this._logoRasterCache.get(resolvedUrl);
            if (cached) return cached;

            try {
                const img = await this._loadImage(resolvedUrl);
                const raster = this._imageToEscStar(img, 320, 200) || this._imageToRaster(img, 320, 200);
                if (!raster) return null;
                const bytes = this._concatBytes([
                    this.encoder.encode(COMMANDS.ALIGN_CENTER),
                    raster,
                    new Uint8Array([0x0A]),
                ]);
                this._logoRasterCache.set(resolvedUrl, bytes);
                return bytes;
            } catch (e) {
                return null;
            }
        }

        _imageToEscStar(img, maxWidth, maxHeight) {
            const srcW = img?.naturalWidth || img?.width || 0;
            const srcH = img?.naturalHeight || img?.height || 0;
            if (!srcW || !srcH) return null;

            const scale = Math.min(1, maxWidth / srcW, maxHeight / srcH);
            const w = Math.max(1, Math.floor(srcW * scale));
            const h = Math.max(1, Math.floor(srcH * scale));

            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            if (!ctx) return null;

            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, w, h);
            ctx.drawImage(img, 0, 0, w, h);

            let imageData;
            try {
                imageData = ctx.getImageData(0, 0, w, h).data;
            } catch (e) {
                return null;
            }

            const isBlackAt = (x, y) => {
                if (x < 0 || x >= w || y < 0 || y >= h) return false;
                const idx = (y * w + x) * 4;
                const r = imageData[idx];
                const g = imageData[idx + 1];
                const b = imageData[idx + 2];
                const a = imageData[idx + 3];
                const lum = 0.2126 * r + 0.7152 * g + 0.0722 * b;
                return a > 16 && lum < 220;
            };

            const out = [];

            out.push(new Uint8Array([0x1B, 0x33, 0x00]));

            for (let y = 0; y < h; y += 24) {
                const nL = w & 0xFF;
                const nH = (w >> 8) & 0xFF;
                out.push(new Uint8Array([0x1B, 0x2A, 0x21, nL, nH]));

                const line = new Uint8Array(w * 3);
                let offset = 0;

                for (let x = 0; x < w; x++) {
                    for (let k = 0; k < 3; k++) {
                        let byte = 0;
                        for (let bit = 0; bit < 8; bit++) {
                            const yy = y + k * 8 + bit;
                            if (isBlackAt(x, yy)) {
                                byte |= (0x80 >> bit);
                            }
                        }
                        line[offset++] = byte;
                    }
                }

                out.push(line);
                out.push(new Uint8Array([0x0A]));
            }

            out.push(new Uint8Array([0x1B, 0x32]));

            return this._concatBytes(out);
        }

        _resolveImageUrl(url) {
            const raw = String(url || '').trim();
            if (raw === '') return null;

            if (raw.startsWith('/')) {
                return `${window.location.origin}${raw}`;
            }

            try {
                const parsed = new URL(raw, window.location.origin);
                const host = String(parsed.hostname || '');
                if ((host === '127.0.0.1' || host === 'localhost') && host !== window.location.hostname) {
                    return `${window.location.origin}${parsed.pathname}${parsed.search}${parsed.hash}`;
                }
                return parsed.toString();
            } catch (e) {
                return null;
            }
        }

        _loadImage(url) {
            return new Promise((resolve, reject) => {
                const attemptDirect = () => {
                    const img = new Image();
                    const shouldSetCors = (() => {
                        try {
                            const parsed = new URL(url, window.location.origin);
                            if (parsed.protocol === 'data:') return false;
                            return parsed.origin !== window.location.origin;
                        } catch (e) {
                            return false;
                        }
                    })();
                    if (shouldSetCors) {
                        img.crossOrigin = 'anonymous';
                    }
                    img.onload = () => resolve(img);
                    img.onerror = (e) => reject(e);
                    img.src = url;
                };

                try {
                    const parsed = new URL(url, window.location.origin);
                    if (parsed.protocol === 'data:') {
                        attemptDirect();
                        return;
                    }
                } catch (e) {
                    attemptDirect();
                    return;
                }

                fetch(url, { cache: 'no-store' })
                    .then((r) => {
                        if (!r.ok) throw new Error(`HTTP ${r.status}`);
                        return r.blob();
                    })
                    .then((blob) => {
                        const objectUrl = URL.createObjectURL(blob);
                        const img = new Image();
                        img.onload = () => {
                            URL.revokeObjectURL(objectUrl);
                            resolve(img);
                        };
                        img.onerror = (e) => {
                            URL.revokeObjectURL(objectUrl);
                            attemptDirect();
                        };
                        img.src = objectUrl;
                    })
                    .catch(() => attemptDirect());
            });
        }

        _imageToRaster(img, maxWidth, maxHeight) {
            const srcW = img?.naturalWidth || img?.width || 0;
            const srcH = img?.naturalHeight || img?.height || 0;
            if (!srcW || !srcH) return null;

            const scale = Math.min(1, maxWidth / srcW, maxHeight / srcH);
            const w = Math.max(1, Math.floor(srcW * scale));
            const h = Math.max(1, Math.floor(srcH * scale));

            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            if (!ctx) return null;

            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, w, h);
            ctx.drawImage(img, 0, 0, w, h);

            let imageData;
            try {
                imageData = ctx.getImageData(0, 0, w, h).data;
            } catch (e) {
                return null;
            }
            const widthBytes = Math.ceil(w / 8);
            const bitmap = new Uint8Array(widthBytes * h);

            for (let y = 0; y < h; y++) {
                for (let xByte = 0; xByte < widthBytes; xByte++) {
                    let byte = 0;
                    for (let bit = 0; bit < 8; bit++) {
                        const x = xByte * 8 + bit;
                        if (x >= w) continue;
                        const idx = (y * w + x) * 4;
                        const r = imageData[idx];
                        const g = imageData[idx + 1];
                        const b = imageData[idx + 2];
                        const a = imageData[idx + 3];
                        const lum = 0.2126 * r + 0.7152 * g + 0.0722 * b;
                        const isBlack = a > 16 && lum < 220;
                        if (isBlack) {
                            byte |= (0x80 >> bit);
                        }
                    }
                    bitmap[y * widthBytes + xByte] = byte;
                }
            }

            const xL = widthBytes & 0xFF;
            const xH = (widthBytes >> 8) & 0xFF;
            const yL = h & 0xFF;
            const yH = (h >> 8) & 0xFF;
            const header = new Uint8Array([0x1D, 0x76, 0x30, 0x00, xL, xH, yL, yH]);
            return this._concatBytes([header, bitmap]);
        }

        /**
         * Generate Kitchen Receipt
         * @param {Object} data 
         * @param {String} stationName 
         */
        kitchenCopy(data, stationName = "KITCHEN") {
            let receipt = COMMANDS.RESET;

            // Header
            receipt += COMMANDS.ALIGN_CENTER;
            receipt += COMMANDS.TEXT_DOUBLE_HEIGHT;
            receipt += COMMANDS.TEXT_DOUBLE_WIDTH;
            receipt += COMMANDS.BOLD_ON;
            const queueNumber = data?.order?.queue_number;
            if (queueNumber !== undefined && queueNumber !== null && String(queueNumber).trim() !== '') {
                receipt += `ANTRIAN ${queueNumber}\n`;
            }
            receipt += COMMANDS.TEXT_NORMAL;
            receipt += COMMANDS.BOLD_OFF;
            receipt += `*** ${stationName.toUpperCase()} ***\n\n`;
            receipt += COMMANDS.ALIGN_LEFT;
            
            receipt += "Kode Trx: " + (data.order?.code || '-') + "\n";
            receipt += "Pelanggan: " + (data.customer_name || 'Guest') + "\n";
            let tableInfo = 'Take Away';
            if (data.order?.order_type === 'dine_in') {
                tableInfo = "Meja " + (data.table_number || data.order?.dining_table?.table_number || '-');
            }
            receipt += "Tipe  : " + tableInfo + "\n";
            receipt += "Waktu : " + (new Date().toLocaleTimeString()) + "\n";
            receipt += "================================\n\n";

            // Items
            receipt += COMMANDS.BOLD_ON;
            if (data.items && Array.isArray(data.items)) {
                data.items.forEach(item => {
                    // Filter logic could happen here or in Manager, but assuming data is already filtered for this kitchen
                    const productName = item.product?.name || item.name || 'Item';
                    const variantName = item.variant_name || item.product_variant?.name || '';
                    const fullName = variantName ? `${productName} (${variantName})` : productName;
                    
                    receipt += `${item.quantity} x ${fullName}\n`;
                    if (item.note) {
                        receipt += `   Catatan: ${item.note}\n`;
                    }
                    receipt += "\n";
                });
            }
            receipt += COMMANDS.BOLD_OFF;

            receipt += "================================\n";
            receipt += "\n\n";
            receipt += COMMANDS.CUT_PAPER;

            return this.encoder.encode(receipt);
        }
    }

    window.ReceiptTemplates = new ReceiptTemplates();
}
