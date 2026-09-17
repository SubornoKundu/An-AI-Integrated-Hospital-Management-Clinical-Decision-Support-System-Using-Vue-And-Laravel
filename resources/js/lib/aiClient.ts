function getXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function aiPost<T = Record<string, unknown>>(
    url: string,
    body: Record<string, unknown> = {},
): Promise<{ ok: boolean; data: T; status: number }> {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': getXsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    let data: T;

    try {
        data = await res.json();
    } catch {
        data = { error: 'Unexpected server response. Please try again.' } as T;
    }

    return { ok: res.ok, data, status: res.status };
}

/** Same as aiPost, but sends multipart/form-data — use for file/audio uploads. */
export async function aiPostForm<T = Record<string, unknown>>(
    url: string,
    form: FormData,
): Promise<{ ok: boolean; data: T; status: number }> {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': getXsrfToken(),
        },
        credentials: 'same-origin',
        body: form,
    });

    let data: T;

    try {
        data = await res.json();
    } catch {
        data = { error: 'Unexpected server response. Please try again.' } as T;
    }

    return { ok: res.ok, data, status: res.status };
}
