/**
 * BluetoothService.js
 * Handles low-level Web Bluetooth API interactions.
 */

if (!window.BluetoothService) {
    class BluetoothService {
        constructor() {
            this.serviceUUID = "000018f0-0000-1000-8000-00805f9b34fb";
            this.characteristicUUID = "00002af1-0000-1000-8000-00805f9b34fb";
            this.chunkSize = 180; // Safe chunk size for BLE
            this.chunkDelayMs = 0;
        }

        /**
         * Request a user to select a Bluetooth device.
         * Must be triggered by a user gesture.
         */
        async requestDevice() {
            try {
                const device = await navigator.bluetooth.requestDevice({
                    filters: [
                        { namePrefix: "RPP" },
                        { namePrefix: "Thermal" },
                        { namePrefix: "POS" },
                        { namePrefix: "BlueTooth" },
                        { namePrefix: "Printer" }
                    ],
                    optionalServices: [this.serviceUUID]
                });
                return device;
            } catch (error) {
                console.error("Bluetooth Request Error:", error);
                throw error;
            }
        }

        /**
         * Get list of permitted devices (already paired/granted).
         */
        async getDevices() {
            if (!navigator.bluetooth || !navigator.bluetooth.getDevices) {
                console.warn("navigator.bluetooth.getDevices() is not supported.");
                return [];
            }
            try {
                return await navigator.bluetooth.getDevices();
            } catch (error) {
                console.error("Get Devices Error:", error);
                return [];
            }
        }

        /**
         * Connect to a specific device's GATT server with robust handling.
         * Includes watchAdvertisements, timeout, and exponential backoff.
         * @param {BluetoothDevice} device 
         * @param {number} retries 
         */
        async connect(device, retries = 3) {
            if (!device) throw new Error("No device provided");

            if (device.gatt.connected) {
                return device.gatt;
            }

            let attempt = 0;
            
            while (attempt < retries) {
                try {
                    attempt++;
                    if (window.PRINTER_DEBUG) console.log(`Connection attempt ${attempt}/${retries}...`);
                    return await this._connectWithAdvertisement(device);
                } catch (error) {
                    console.warn(`Connection attempt ${attempt} failed:`, error);
                    
                    if (attempt >= retries) {
                        throw error;
                    }
                    
                    // Exponential backoff: 1s, 2s, 4s
                    const delay = 1000 * Math.pow(2, attempt - 1);
                    if (window.PRINTER_DEBUG) console.log(`Retrying in ${delay}ms...`);
                    await new Promise(resolve => setTimeout(resolve, delay));
                }
            }
        }

        /**
         * Internal helper to connect using advertisement signal
         */
        async _connectWithAdvertisement(device) {
             return new Promise(async (resolve, reject) => {
                const abortController = new AbortController();
                
                let timer = null;
                let isHandled = false;

                const cleanup = () => {
                    if (timer) clearTimeout(timer);
                    device.removeEventListener('advertisementreceived', handleAdvertisement);
                    
                    // Safely check if unwatchAdvertisements exists before calling
                    if (device.watchingAdvertisements && typeof device.unwatchAdvertisements === 'function') {
                         device.unwatchAdvertisements().catch(e => console.warn("Error unwatching", e));
                    }
                };

                const handleAdvertisement = async (event) => {
                    if (isHandled) return;
                    isHandled = true;
                    
                    if (window.PRINTER_DEBUG) console.log(`Signal received from ${device.name} (RSSI: ${event.rssi})`);
                    cleanup();

                    try {
                        if (window.PRINTER_DEBUG) console.log("Connecting to GATT...");
                        const server = await device.gatt.connect();
                        
                        if (window.PRINTER_DEBUG) console.log("Verifying service...");
                        await server.getPrimaryService(this.serviceUUID);
                        
                        device.addEventListener('gattserverdisconnected', this.onDisconnected.bind(this));
                        resolve(server);
                    } catch (err) {
                        reject(err);
                    }
                };

                // Timeout logic (10 seconds)
                timer = setTimeout(() => {
                    if (isHandled) return;
                    isHandled = true;
                    console.warn("Connection timed out (10s) - device not in range.");
                    abortController.abort();
                    cleanup();
                    reject(new Error("NetworkError: Bluetooth Device is no longer in range (Timeout)"));
                }, 10000);

                try {
                     if (typeof device.watchAdvertisements === 'function') {
                        device.addEventListener('advertisementreceived', handleAdvertisement);
                        await device.watchAdvertisements({ signal: abortController.signal });
                        if (window.PRINTER_DEBUG) console.log(`Watching advertisements for ${device.name}...`);
                     } else {
                         // Fallback for browsers without watchAdvertisements
                         if (window.PRINTER_DEBUG) console.log("watchAdvertisements not supported, attempting direct connect.");
                         cleanup();
                         const server = await device.gatt.connect();
                         await server.getPrimaryService(this.serviceUUID);
                         device.addEventListener('gattserverdisconnected', this.onDisconnected.bind(this));
                         resolve(server);
                     }
                } catch (err) {
                    if (!isHandled) {
                        isHandled = true;
                        cleanup();
                        console.warn("watchAdvertisements error, falling back to direct connect:", err);
                        try {
                            const server = await device.gatt.connect();
                            await server.getPrimaryService(this.serviceUUID);
                            device.addEventListener('gattserverdisconnected', this.onDisconnected.bind(this));
                            resolve(server);
                        } catch (directErr) {
                            reject(directErr);
                        }
                    }
                }
             });
        }

        onDisconnected(event) {
            const device = event.target;
            console.warn(`Device ${device.name} disconnected`);
            // Trigger a custom event that PrinterManager can listen to
            window.dispatchEvent(new CustomEvent('printer-disconnected', { detail: { device } }));
        }

        /**
         * Send data to the printer.
         * @param {BluetoothRemoteGATTServer} server 
         * @param {Uint8Array} data 
         */
        async print(server, data) {
            if (!server || !server.connected) {
                throw new Error("Printer not connected");
            }

            try {
                const service = await server.getPrimaryService(this.serviceUUID);
                const characteristic = await service.getCharacteristic(this.characteristicUUID);
                
                await this.sendChunks(characteristic, data);
            } catch (error) {
                console.error("Print Error:", error);
                throw error;
            }
        }

        /**
         * Send data in chunks to avoid buffer overflow.
         * @param {BluetoothRemoteGATTCharacteristic} characteristic 
         * @param {Uint8Array} data 
         */
        async sendChunks(characteristic, data) {
            let offset = 0;
            const delayMs = data && data.length > 4096 ? 10 : this.chunkDelayMs;
            while (offset < data.length) {
                const chunk = data.slice(offset, offset + this.chunkSize);
                await characteristic.writeValue(chunk);
                offset += this.chunkSize;
                if (delayMs > 0) {
                    await new Promise(resolve => setTimeout(resolve, delayMs));
                }
            }
        }
    }

    window.BluetoothService = new BluetoothService();
}
