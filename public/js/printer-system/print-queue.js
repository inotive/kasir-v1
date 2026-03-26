/**
 * PrintQueue.js
 * Manages print jobs with priority and retry logic.
 */

if (!window.PrintQueue) {
    class PrintQueue {
        constructor() {
            this.queue = [];
            this.isProcessing = false;
            this.maxRetries = 3;
        }

        /**
         * Add a job to the queue
         * @param {Object} job { printerRole, data, retries }
         */
        add(job) {
            if (window.PRINTER_DEBUG) {
                try {
                    const role = job?.printerRole;
                    const meta = {
                        printerRole: role,
                        bytes: typeof job?.data === 'string' ? job.data.length : null,
                        timestamp: new Date().toISOString(),
                    };
                    console.info('[PrintQueue] add', meta);
                } catch (e) {
                }
            }
            this.queue.push({
                ...job,
                retries: 0,
                timestamp: Date.now()
            });
            this.processNext();
        }

        async processNext() {
            if (this.isProcessing || this.queue.length === 0) {
                return;
            }

            this.isProcessing = true;
            const job = this.queue.shift();

            try {
                if (window.PRINTER_DEBUG) {
                    try {
                        console.info('[PrintQueue] processing', {
                            printerRole: job?.printerRole,
                            retries: job?.retries ?? 0,
                            queuedAt: job?.timestamp ? new Date(job.timestamp).toISOString() : null,
                        });
                    } catch (e) {
                    }
                }

                if (typeof job?.data === 'function') {
                    job.data = job.data();
                }
                if (job?.data && typeof job.data.then === 'function') {
                    job.data = await job.data;
                }
                await window.PrinterManager.processJob(job);
            } catch (error) {
                console.error('[PrintQueue] job failed', error);
                const message = String(error?.message || '');
                const code = String(error?.code || '');
                const name = String(error?.name || '');
                const isConnectivity =
                    code === 'MISSING_CONFIG' ||
                    code === 'PERMISSION_LOST' ||
                    code === 'AUTO_RECONNECT_LIMIT' ||
                    message.includes('Bluetooth') ||
                    message.includes('Printer not connected') ||
                    message.includes('no longer in range') ||
                    name === 'NetworkError';

                if (isConnectivity) {
                    window.dispatchEvent(new CustomEvent('printer-job-failed', {
                        detail: { printerRole: job.printerRole, message: message || 'Gagal mencetak karena printer offline.' }
                    }));
                } else if (job.retries < this.maxRetries) {
                    job.retries++;
                    this.queue.push(job); // Re-queue at the end (or unshift for immediate retry)
                } else {
                    window.dispatchEvent(new CustomEvent('printer-job-failed', {
                        detail: { printerRole: job.printerRole, message: `Gagal mencetak setelah ${this.maxRetries}x percobaan.` }
                    }));
                }
            } finally {
                this.isProcessing = false;
                // Add a small delay to prevent congestion
                setTimeout(() => this.processNext(), 500);
            }
        }
    }

    window.PrintQueue = new PrintQueue();
}
