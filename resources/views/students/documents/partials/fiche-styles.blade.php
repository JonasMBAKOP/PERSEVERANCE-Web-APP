<style>
@page { size: A4 portrait; margin: 2mm 3mm; }
.fiche-page { color: #111827; }
.page.fiche-page {
    max-width: 204mm;
    padding: 0 3mm 2mm;
}
.fiche-page .cert-official-header { margin-bottom: 4px; }
.fiche-document-title {
    background: #d9d9d9;
    border-top: 1px solid #9CA3AF;
    border-bottom: 1px solid #9CA3AF;
    padding: 6px 8px;
    margin-bottom: 16px;
    text-align: center;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 24px;
    font-weight: 900;
    line-height: 1.15;
    text-transform: uppercase;
}
.fiche-top { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
.fiche-identity { flex: 1; }
.fiche-name { font-size: 16px; font-weight: 900; color: #111827; margin-bottom: 4px; }
.fiche-matricule { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #111827; }
.fiche-inline-label {
    display: inline-flex;
    flex-direction: column;
    gap: 1px;
    line-height: 1.05;
}
.fiche-matricule em,
.fiche-classline em,
.fiche-label em,
.fiche-section__title em { font-style: italic; font-weight: 600; }
.fiche-classline { display: flex; flex-wrap: wrap; align-items: center; gap: 5px 8px; margin-top: 7px; font-size: 10px; }
.fiche-section { margin-top: 12px; border: 1px solid #D1D5DB; }
.fiche-section__title {
    display: flex;
    align-items: baseline;
    gap: 8px;
    padding: 6px 8px;
    background: #F3F4F6;
    border-bottom: 1px solid #D1D5DB;
    color: #111827;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
}
.fiche-section__title em { font-size: 9px; text-transform: none; }
.fiche-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.fiche-field {
    display: grid;
    grid-template-columns: 34mm 1fr;
    min-height: 9mm;
    border-bottom: 1px solid #E5E7EB;
    border-right: 1px solid #E5E7EB;
}
.fiche-field:nth-child(2n) { border-right: 0; }
.fiche-field--wide {
    grid-column: 1 / -1;
    grid-template-columns: 44mm 1fr;
    border-right: 0;
}
.fiche-field:nth-last-child(-n+2):not(.fiche-field--wide),
.fiche-field:last-child { border-bottom: 0; }
.fiche-label {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 1px;
    padding: 4px 6px;
    color: #111827;
    font-size: 10px;
    font-weight: 400;
}
.fiche-label em { font-size: 8px; color: #374151; }
.fiche-value {
    display: flex;
    align-items: center;
    padding: 4px 6px;
    color: #111827;
    font-size: 10.5px;
    font-weight: 900;
    word-break: break-word;
}
.fiche-principal-signature {
    width: 56mm;
    margin-top: 2mm;
    margin: 2mm 5mm 0 auto;
    margin-bottom: 8mm;
    text-align: center;
    color: #111827;
    font-size: 14px;
    font-weight: 900;
}
.fiche-principal-signature--tight {
    margin-top: 2mm;
    margin-bottom: 2mm;
}
.fiche-principal-signature__en {
    margin-top: 1px;
    font-size: 10px;
    font-style: italic;
    font-weight: 700;
}
.fiche-principal-signature__seal {
    margin-top: 8px;
}
.fiche-principal-signature__seal img {
    max-width: 115px;
    max-height: 115px;
    display: block;
    margin: 6px auto 0;
    margin-top: 0;
}
@media print {
    .fiche-print-page { page-break-after: always; }
    .fiche-print-page:last-child { page-break-after: auto; }
}
</style>
