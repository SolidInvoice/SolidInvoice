import type { Client } from '../../../types/client';

export default function (client: Client): string {
    return client.name;
}
