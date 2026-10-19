// Keep the country code visible outside the editable national number.
export function nationalNumber(value) {
    let digits = value.replace(/\D/g, "");
    if (digits.startsWith("00961")) digits = digits.slice(5);
    else if (digits.startsWith("961")) digits = digits.slice(3);
    return digits.replace(/^0/, "");
}
export function validPhone(value) {
    return /^\+?[0-9 ()-]+$/.test(value.trim()) && (!value.trim().startsWith('+') || value.replace(/\D/g, '').startsWith('961')) && /^(?:[1-9]\d{6}|(?:70|71|76|78|79|81)\d{6})$/.test(nationalNumber(value));
}
export function formatPhone(input) {
    if (input.value.trim().startsWith('+') && !input.value.replace(/\D/g, '').startsWith('961')) return;
    const national = nationalNumber(input.value);
    // Keep 03 recognisable locally. Other landlines can be entered as 01, 04, etc.
    const raw = input.value.replace(/\D/g, "");
    if (raw === "0") return;
    const trunk = raw.startsWith("0") && !raw.startsWith("00961");
    let digits = national.startsWith("3") || trunk ? `0${national}` : national;
    const groups = /^(?:0|7|8)/.test(digits) ? [2, 3, 3] : [1, 3, 3];
    let offset = 0;
    const parts = groups.map((length) => { const part = digits.slice(offset, offset + length); offset += length; return part; });
    if (digits.length > offset) parts.push(digits.slice(offset));
    input.value = parts.filter(Boolean).join(" ");
}
