import axios from 'axios';
import Vue from 'vue';

interface RequestInterface {
    command: string | undefined,
    params?: object,
}

export interface ResponseInterface {
    data: {
        totalCount?: number,
        items: any[],
    },
    status: 'success' | 'error',
}

export class Request {
    static async post(data: RequestInterface): Promise<any> {
        try {
            const response = await axios.post(
                'addonmodules.php?module=CloudLinuxAddon',
                data,
                {
                    headers: {
                        'content-type': 'application/json',
                        'CL-CSRF-TOKEN': csrfToken,
                    },
                },
            );
            return response.data;
        } catch (e) {
            (Vue as any).notify({
                group: 'service',
                type: 'error',
                title: 'Error',
                text: e.response.data.message,
            });
            throw new Error(e.response.data.message);
        }
    }
}