<style>
@page { size: A4 portrait; margin: 5mm; }
@media print {
    .no-print { display: none !important; }
    body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 16px;
    color: #111827;
    background: #ffffff;
    padding: 0;
    margin: 0;
}
.meta-line {
    margin: 0 !important;
    padding: 0 !important;
}
.data-table {
    margin: 0 !important;
    padding: 0 !important;
}
.page {
    width: 100%;
    max-width: none;
    margin: 0;
    background: #fff;
    padding: 0;
}
.page.cert-page {
    max-width: 196mm;
    padding: 4mm 6mm;
}
.doc-header {
    border-bottom: 2.5px solid #1A3A6B;
    padding-bottom: 8px;
    margin-bottom: 14px;
    text-align: center;
}
.doc-header__school {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 6px;
}
.doc-header img { height: 48px; width: 48px; object-fit: contain; }
.doc-header__logo-placeholder {
    width: 68px; height: 68px; border-radius: 50%;
    background: #1A3A6B; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 26px;
}
.doc-header__name { font-size: 24px; font-weight: 900; color: #1A3A6B; line-height: 1.1; }
.doc-header__motto { font-size: 13px; font-style: italic; color: #6B7280; margin-top: 4px; }
.doc-header__meta { font-size: 13px; color: #4B5563; margin-top: 3px; }
.doc-header__ministry { font-size: 12px; color: #6B7280; margin-top: 2px; }
.doc-header__title {
    font-size: 24px; font-weight: 900; text-transform: uppercase;
    color: #9c4005; letter-spacing: 0.08em; margin-top: 10px;
}
.doc-header__subtitle { font-size: 18px; color: #374151; margin-top: 4px; }
.doc-header--compact .doc-header__name { font-size: 13px; }
.doc-header--compact img, .doc-header--compact .doc-header__logo-placeholder {
    height: 32px; width: 32px; font-size: 12px;
}
.section-title {
    font-size: 16px; font-weight: 900; text-transform: uppercase;
    color: #1A3A6B; border-bottom: 1px solid #E5E7EB;
    padding-bottom: 4px; margin: 12px 0 8px;
}
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 16px;
}
.info-row { display: flex; gap: 8px; font-size: 14px; line-height: 1.5; }
.info-row .label { color: #6B7280; min-width: 120px; flex-shrink: 0; }
.info-row .value { font-weight: 700; color: #111827; }
.info-row.full { grid-column: 1 / -1; }
.photo-box {
    width: 78px; height: 96px; border: 1px solid #D1D5DB;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; background: #F9FAFB; flex-shrink: 0;
}
.photo-box img { width: 100%; height: 100%; object-fit: cover; }
.photo-placeholder {
    font-size: 22px; font-weight: 900; color: #1A3A6B;
}
.signature-block {
    margin-top: 28px; display: flex; justify-content: space-between; gap: 20px;
}
.signature-box { text-align: center; flex: 1; font-size: 10px; }
.signature-line {
    border-top: 1px solid #374151; margin-top: 48px; padding-top: 4px;
    font-weight: 700;
}
.footer-note {
    margin-top: 16px; font-size: 8px; color: #9CA3AF; text-align: center;
}

/* Bilingual Header Styles */
.bilingual-header {
    border-bottom: 2.5px solid #1A3A6B;
    padding-bottom: 10px;
    margin-bottom: 16px;
    text-align: center;
}
.bilingual-header__logo {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 8px;
}
.bilingual-header__logo img {
    height: 55px;
    width: 55px;
    object-fit: contain;
}
.bilingual-header__logo-placeholder {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: #1A3A6B;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 20px;
}
.bilingual-header__info {
    display: inline-block;
    vertical-align: top;
    width: 48%;
    text-align: left;
}
.bilingual-header__info--fr {
    margin-right: 2%;
}
.bilingual-header__info--en {
    margin-left: 2%;
    text-align: right;
}
.bilingual-header__name {
    font-size: 13px;
    font-weight: 900;
    color: #1A3A6B;
    line-height: 1.3;
    margin-bottom: 3px;
}
.bilingual-header__motto {
    font-size: 8.5px;
    font-style: italic;
    color: #6B7280;
    margin-bottom: 3px;
}
.bilingual-header__meta {
    font-size: 8px;
    color: #4B5563;
    line-height: 1.4;
    margin-bottom: 3px;
}
.bilingual-header__ministry {
    font-size: 7.5px;
    color: #6B7280;
    margin-bottom: 3px;
}
.bilingual-header__agreements {
    font-size: 7.5px;
    color: #1A3A6B;
    font-weight: 700;
    margin-top: 4px;
}
.bilingual-header__agreement {
    display: inline-block;
    margin-right: 8px;
}
.bilingual-header__agreement:last-child {
    margin-right: 0;
}
.bilingual-header__title {
    margin-top: 10px;
    font-size: 14px;
    font-weight: 900;
    text-transform: uppercase;
    color: #9c4005;
    letter-spacing: 0.5px;
}
.bilingual-header__title--fr,
.bilingual-header__title--en {
    display: inline-block;
}
.bilingual-header__title--fr {
    margin-right: 15px;
}
.bilingual-header__subtitle {
    font-size: 10px;
    color: #374151;
    margin-top: 4px;
}
.bilingual-header--compact .bilingual-header__name {
    font-size: 11px;
}
.bilingual-header--compact .bilingual-header__logo img,
.bilingual-header--compact .bilingual-header__logo-placeholder {
    height: 40px;
    width: 40px;
    font-size: 16px;
}
.bilingual-header--compact .bilingual-header__meta {
    font-size: 7px;
}

.bordereau-header {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 18px;
        align-items: start;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #CBD5E1;
    }
    .bordereau-header__brand {
        display: flex;
        gap: 14px;
        align-items: center;
    }
    .bordereau-header__logo {
        width: 88px;
        min-width: 88px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bordereau-header__logo img {
        max-height: 84px;
        max-width: 84px;
        object-fit: contain;
    }
    .bordereau-header__logo-placeholder {
        width: 84px;
        height: 84px;
        border-radius: 18px;
        background: #1A3A6B;
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 32px;
        font-weight: 900;
    }
    .bordereau-header__school-info {
        display: grid;
        gap: 4px;
    }
    .bordereau-header__school {
        font-size: 14px;
        font-weight: 900;
        color: #1A3A6B;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .bordereau-header__meta {
        font-size: 10px;
        color: #475569;
    }
    .bordereau-header__doc {
        display: grid;
        gap: 4px;
        justify-items: end;
        text-align: right;
    }
    .bordereau-header__doc-title {
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
        color: #1A3A6B;
        letter-spacing: .05em;
    }
    .bordereau-header__doc-copy {
        font-size: 12px;
        font-weight: 700;
        color: #CC6000;
    }
    .bordereau-header__doc-year,
    .bordereau-header__doc-date {
        font-size: 12px;
        color: #1A3A6B;
        font-weight: 700;
    }
    .bordereau-header__title-row {
        margin-top: 14px;
        text-align: center;
        grid-column: 1 / -1;
    }
    .bordereau-header__title {
        font-size: 24px;
        font-weight: 900;
        color: #1A3A6B;
        text-transform: uppercase;
        letter-spacing: .1em;
        text-decoration: underline;
    }
    .bordereau-header__subtitle {
        font-size: 15px;
        color: black;
        font-weight: 700;
        margin-top: 4px;
    }

/* Certificate official header */
.cert-official-header {
    margin-bottom: 18px;
    color: #111827;
}
.cert-official-header__columns {
    display: grid;
    grid-template-columns: 1fr 40mm 1fr;
    align-items: center;
    gap: 6mm;
}
.cert-official-header__side {
    min-height: 48mm;
    font-family: Georgia, 'Times New Roman', serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}
.cert-official-header__side--fr {
    text-align: center;
}
.cert-official-header__side--en {
    text-align: center;
}
.cert-official-header__republic {
    font-size: 12.5px;
    font-weight: 900;
    line-height: 1.15;
    text-transform: uppercase;
}
.cert-official-header__motto {
    font-size: 11px;
    font-style: italic;
    font-weight: 700;
    line-height: 1.15;
    /* margin: 2px 0; */
    margin: 4px 0 3px;
}
.cert-official-header__stars {
    font-size: 12px;
    font-weight: 900;
    line-height: 1.1;
    margin: 2px 0;
    letter-spacing: 1px;
}
.cert-official-header__ministry {
    font-size: 11.5px;
    font-weight: 900;
    line-height: 1.15;
    text-transform: uppercase;
}
.cert-official-header__school {
    font-size: 11.5px;
    font-style: italic;
    font-weight: 900;
    line-height: 1.18;
    margin-top: 0;
    text-transform: uppercase;
}
.cert-official-header__meta,
.cert-official-header__email {
    font-size: 9.5px;
    font-weight: 700;
    line-height: 1.35;
}
.cert-official-header__email span {
    font-size: 10.5px;
    font-weight: 900;
}
.cert-official-header__logo {
    display: flex;
    align-items: center;
    justify-content: center;
}
.cert-official-header__logo img {
    width: 36mm;
    height: 36mm;
    object-fit: contain;
}
.cert-official-header__logo-placeholder {
    width: 36mm;
    height: 36mm;
    border: 2px solid #1A3A6B;
    border-radius: 999px;
    color: #1A3A6B;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 28px;
    font-weight: 900;
}
.cert-official-header__agreements {
    /* margin: -2px auto 5px; */
    margin: 2px auto 8px;
    max-width: 150mm;
    text-align: center;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 10.5px;
    font-style: italic;
    font-weight: 900;
    line-height: 1.25;
}
.cert-official-header__agreements div {
    text-align: center;
}
.cert-official-header__title {
    background: #d9d9d9;
    border-top: 1px solid #9CA3AF;
    border-bottom: 1px solid #9CA3AF;
    padding: 6px 8px;
    text-align: center;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 28px;
    font-weight: 900;
    line-height: 1.15;
    text-transform: uppercase;
}
.cert-official-header__title div + div {
    font-size: 23px;
    font-style: italic;
    margin-top: 3px;
}

/* Certificate body */
.cert-attestation {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 15px;
    line-height: 1.22;
    margin-top: 20px;
}
.cert-line {
    margin-bottom: 13px;
}
.cert-label {
    font-weight: 400;
}
.cert-value {
    display: inline-block;
    margin-left: 8px;
    font-size: 16px;
    font-weight: 900;
}
.cert-value--school {
    font-size: 16px;
}
.cert-translation {
    margin-top: 1px;
    font-size: 10.5px;
    font-style: italic;
    font-weight: 700;
}
.cert-grid {
    display: grid;
    gap: 18mm;
}
.cert-grid--birth {
    grid-template-columns: 68mm 1fr;
    gap: 34mm;
}
.cert-grid--birth .cert-line > div:first-child {
    white-space: nowrap;
}
.cert-grid--class {
    grid-template-columns: 1fr 58mm;
    gap: 12mm;
}
.cert-registration {
    display: grid;
    grid-template-columns: 1fr 42mm;
    align-items: end;
    gap: 8mm;
    margin-bottom: 13px;
}
.cert-registration .cert-line {
    margin-bottom: 0;
}
.cert-registration__number {
    font-size: 17px;
    font-weight: 900;
    text-align: right;
}
.cert-line--statement {
    margin-top: 18px;
}
.cert-signature {
    display: grid;
    grid-template-columns: 1fr 58mm;
    align-items: start;
    gap: 12mm;
    margin-top: 30px;
    margin-left: 55mm;
}
.cert-signature__date {
    font-size: 14px;
    margin-top: 30px;
    width: 300px;
    margin-left: -100px;
}
.cert-signature__date strong {
    margin-left: 8px;
    font-size: 17px;
    font-weight: 900;
}
.cert-signature__principal {
    margin-top: 80px;
    text-align: center;
    font-size: 16px;
    font-weight: 900;
}
.cert-signature__principal .cert-translation {
    font-size: 10.5px;
}
.cert-signature__seal {
    margin-top: 8px;
}
.cert-signature__seal img {
    max-width: 150px;
    max-height: 150px;
    display: block;
    margin: 6px auto 0;
}
.cert-note {
    margin-top: 30px;
    text-align: center;
    font-size: 12px;
    font-weight: 700;
}
.cert-note .cert-translation {
    font-size: 10px;
}
</style>
