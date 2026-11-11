export function formatCurrency(value, withDecimals = false) {
    const number = Number(value ?? 0);
    if (Number.isNaN(number)) {
        return 'Rp 0';
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: withDecimals ? 2 : 0,
        maximumFractionDigits: withDecimals ? 2 : 0,
    }).format(number);
}

