import type { Document } from '$types/data';
import { base } from '$app/paths';
import { goto } from '$app/navigation';
import { _ } from '$lib/locale';
import req from '$lib/req';
import toast from '$lib/toast';

export interface Result {
    success: boolean;
    uid: string;
}

async function save(uid: string, doc: Document) {
    const response = await req.put(`node/${uid}`, doc);

    if (response.ok) {
        toast.add({
            kind: 'success',
            message: _('Dokument erfolgreich gespeichert!'),
        });

        return response.data as Result;
    } else {
        const data = response.data;

        toast.add({
            kind: 'error',
            message: data.description
                ? data.description
                : _('Fehler beim Speichern des Dokuments aufgetreten!'),
        });

        return response.data as Result;
    }
}

async function create(doc: Document, type: string, collectionPath: string) {
    const response = await req.post(`node/${type}`, doc);

    if (response.ok) {
        await goto(`${base}/${collectionPath}/${doc.uid}`, {
            invalidateAll: true,
        });

        toast.add({
            kind: 'success',
            message: _('Dokument erfolgreich erstellt!'),
        });

        return response.data as Result;
    } else {
        toast.add({
            kind: 'error',
            message: _('Fehler beim Erstellen des Dokuments aufgetreten!'),
        });

        return response.data as Result;
    }
}

async function remove(uid: string, collectionPath: string) {
    const response = await req.del(`node/${uid}`);

    if (response.ok) {
        await goto(`${base}/${collectionPath}`, { invalidateAll: true });

        toast.add({
            kind: 'success',
            message: _('Dokument erfolgreich gelöscht!'),
        });
    } else {
        toast.add({
            kind: 'error',
            message: _('Fehler beim Löschen des Dokuments aufgetreten!'),
        });
    }
}

export default { save, remove, create };
