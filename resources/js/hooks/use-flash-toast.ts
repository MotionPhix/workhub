import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('success', (event) => {
            const flash = (event.detail.page.props as Record<string, unknown>).flash as Record<string, unknown> | undefined;

            if (!flash) {
                return;
            }

            // Handle toast object format: flash.toast = { type, message }
            if (flash.toast && typeof flash.toast === 'object') {
                const t = flash.toast as { type: string; message: string };
                toast[t.type as 'success' | 'info' | 'warning' | 'error'](t.message);
                return;
            }

            // Handle string format: flash.success / flash.error / flash.info / flash.warning
            for (const type of ['success', 'error', 'info', 'warning'] as const) {
                if (typeof flash[type] === 'string') {
                    toast[type](flash[type] as string);
                    return;
                }
            }
        });
    }, []);
}
