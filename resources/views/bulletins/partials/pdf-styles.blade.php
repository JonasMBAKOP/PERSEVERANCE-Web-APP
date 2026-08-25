<style>
    :root {
        --bleu:  #4A86C8;
        --bleu-dark: #1A3A6B;
        --vert:  #1A5C2A;
        --or:    #C8A415;
        --rouge: #DC2626;
        --gris:  #F8FAFC;
    }

    @page {
        size: A4 portrait;
        margin: 1mm;
    }

    * {
        box-sizing: border-box;
    }

    html, body {
        margin: 0;
        padding: 0;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: #111827;
    }

    body {
        background: #E5E7EB;
    }

    .bulletin-page {
        width: 208mm;
        max-width: 100%;
        min-height: 291mm;
        margin-left: auto;
        margin-right: auto;
        padding: 3mm 4mm;
        background: white;
        border: 4px solid var(--bleu);
        box-sizing: border-box;
        position: relative;
        overflow: hidden;
    }

    .bulletin-header {
        display: grid;
        grid-template-columns: 1fr 1.5fr 1fr;
        gap: 10px;
        align-items: start;
        border-bottom: 3px solid var(--bleu);
        padding-bottom: 12px;
        margin-bottom: 12px;
    }

    .header-left,
    .header-center,
    .header-right {
        min-width: 0;
    }

    .header-left {
        display: grid;
        gap: 3px;
    }

    .header-right {
        display: grid;
        align-items: center;
        justify-items: end;
    }

    .school-logo {
        width: 80px;
        height: 80px;
        object-fit: contain;
        border-radius: 10px;
    }

    .school-logo-placeholder {
        width: 80px;
        height: 80px;
        border: 2px solid var(--bleu);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 900;
        color: var(--bleu);
        text-align: center;
        padding: 6px;
    }

    .header-center {
        text-align: center;
        display: grid;
        gap: 4px;
    }

    .ministry {
        font-size: 8px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #6B7280;
        font-weight: 700;
    }

    .republic {
        font-size: 9px;
        font-weight: 900;
        text-transform: uppercase;
        color: var(--bleu);
        letter-spacing: .08em;
    }

    .motto {
        font-size: 8px;
        color: #9CA3AF;
        letter-spacing: .05em;
    }

    .school-name {
        font-size: 15px;
        font-weight: 900;
        color: var(--bleu);
        margin-top: 3px;
    }

    .school-meta {
        font-size: 8px;
        color: #6B7280;
        margin-top: 2px;
    }

    .document-title {
        margin-top: 6px;
        padding: 4px 12px;
        background: var(--bleu);
        color: white;
        border-radius: 999px;
        font-size: 8.5px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        display: inline-flex;
        justify-content: center;
    }

    .document-subtitle,
    .document-period {
        color: #475569;
        font-size: 8.5px;
        font-weight: 700;
    }

    .document-period {
        font-size: 8px;
        font-weight: 600;
        color: #6B7280;
    }

    .student-card {
        display: grid;
        grid-template-columns: 1.4fr 0.9fr;
        gap: 10px;
        background: var(--gris);
        border: 1.5px solid #E5E7EB;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 12px;
        font-size: 8.5px;
    }

    .school-name {
        font-size: 15px;
        font-weight: 900;
        color: var(--bleu);
        letter-spacing: -.01em;
        margin-top: 5px;
    }

    .school-meta {
        font-size: 8px;
        color: #6B7280;
        margin-top: 4px;
    }

    .header-meta {
        text-align: right;
    }

    .bulletin-badge {
        display: inline-block;
        background: var(--bleu);
        color: white;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 8px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .meta-item {
        font-size: 8px;
        color: #6B7280;
        margin-top: 3px;
    }

    .student-card {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        background: var(--gris);
        border: 1.5px solid #E5E7EB;
        border-radius: 10px;
    }

    .info-label {
        font-size: 7px;
        font-weight: 700;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 1px;
    }

    .info-sublabel {
        font-size: 6px;
        font-weight: 600;
        color: #9CA3AF;
        font-style: italic;
        margin-bottom: 2px;
        line-height: 1;
    }

    .info-value {
        font-weight: 800;
        color: #111827;
        font-size: 8px;
    }

    .notes-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5px;
        margin-bottom: 10px;
        border: 1px solid #9CA3AF;
    }

    .notes-table th,
    .notes-table td {
        padding: 5px 6px;
        border: 1px solid #9CA3AF;
    }

    .notes-table thead {
        background: var(--bleu);
        color: white;
    }

    .notes-table thead th {
        border: 1px solid #9CA3AF;
    }

    .notes-table th {
        font-size: 7.5px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .06em;
        text-align: center;
    }

    .notes-table th.col-subject,
    .notes-table td.col-subject {
        text-align: left;
        width: 30%;
    }

    .student-photo-fallback span { display: none; }

    .notes-table th.col-group,
    .notes-table td.col-group {
        text-align: center;
        width: 8%;
    }

    .notes-table tbody td,
    .notes-table tfoot td {
        padding-top: 2px;
        padding-bottom: 2px;
        line-height: 1.05;
    }

    .notes-table tr:nth-child(even) {
        background: #F9FAFB;
    }

    .grade-cell {
        text-align: center;
        font-weight: 900;
    }

    .grade-good { color: var(--vert); }
    .grade-avg { color: var(--or); }
    .grade-bad { color: var(--rouge); }
    .grade-absent { color: #9CA3AF; font-style: italic; }

    .col-coef,
    .col-grade,
    .col-total,
    .col-rank,
    .col-appr,
    .col-period {
        text-align: center;
    }

    .footer-cell {
        font-weight: 900;
        color: var(--bleu);
    }

    .tfoot-label {
        padding: 10px 8px;
        text-align: left;
        font-weight: 900;
        color: #1F2937;
        background: #F8FAFC;
        border-top: 2px solid var(--bleu);
    }

    .tfoot-value {
        text-align: center;
        font-size: 9px;
        font-weight: 900;
        color: var(--bleu);
        background: #F8FAFC;
        border-top: 2px solid var(--bleu);
    }

    .bilan-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 10px;
    }

    .bilan-card {
        border-radius: 10px;
        padding: 10px;
        text-align: center;
    }

    .bilan-main {
        background: var(--bleu);
        color: white;
    }

    .bilan-green {
        background: rgba(26, 92, 42, 0.1);
        color: var(--vert);
    }

    .bilan-gold {
        background: rgba(200, 164, 21, 0.1);
        color: var(--or);
    }

    .bilan-value {
        font-size: 18px;
        font-weight: 900;
        margin-top: 6px;
    }

    .bilan-label {
        font-size: 7px;
        letter-spacing: .08em;
        text-transform: uppercase;
        opacity: .85;
    }

    .observations-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .obs-box {
        border: 1.5px solid #E5E7EB;
        border-radius: 10px;
        padding: 10px;
        min-height: 70px;
    }

    .obs-title {
        font-size: 8px;
        font-weight: 900;
        color: var(--bleu);
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 5px;
    }

    .obs-content {
        font-size: 8px;
        color: #374151;
        font-weight: 600;
        min-height: 32px;
        line-height: 1.3;
    }

    .signatures-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        border-top: 1px dashed #E5E7EB;
        padding-top: 10px;
    }

    .sig-box {
        text-align: center;
        font-size: 7px;
    }

    .sig-title {
        font-size: 7px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--bleu);
        margin-bottom: 7px;
    }

    .sig-line {
        width: 80%;
        height: 1px;
        background: #D1D5DB;
        margin: 0 auto 5px;
    }

    .sig-name {
        color: #6B7280;
        font-size: 7px;
        font-weight: 700;
    }

    .bulletin-footer {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid #E5E7EB;
        font-size: 7.5px;
        color: #6B7280;
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .page-break { page-break-after: always; }
    .page-break:last-child { page-break-after: auto; }

    /* Bilingual Official Header Styles */
    .cert-official-header {
        margin-bottom: 10px;
        color: #111827;
        border-bottom: 3px solid var(--bleu);
        padding-bottom: 6px;
    }
    .cert-official-header__columns {
        display: grid;
        grid-template-columns: 1fr 40mm 1fr;
        align-items: center;
        gap: 6mm;
    }
    .cert-official-header__side {
        min-height: auto;
        font-family: Georgia, 'Times New Roman', serif;
        text-align: center;
    }
    .cert-official-header__side--fr {
        text-align: center;
    }
    .cert-official-header__side--en {
        text-align: center;
    }
    .cert-official-header__republic {
        font-size: 11px;
        font-weight: 900;
        line-height: 1.15;
        text-transform: uppercase;
        color: var(--bleu-dark);
    }
    .cert-official-header__motto {
        font-size: 9px;
        font-style: italic;
        font-weight: 700;
        line-height: 1.15;
        margin: 4px 0 3px;
    }
    .cert-official-header__stars {
        font-size: 11px;
        font-weight: 900;
        line-height: 1.1;
        margin: 2px 0;
        letter-spacing: 1px;
    }
    .cert-official-header__ministry {
        font-size: 10px;
        font-weight: 900;
        line-height: 1.15;
        text-transform: uppercase;
    }
    .cert-official-header__school {
        font-size: 11px;
        font-style: italic;
        font-weight: 900;
        line-height: 1.18;
        margin-top: 0;
        text-transform: uppercase;
        color: var(--bleu-dark);
    }
    .cert-official-header__meta,
    .cert-official-header__email {
        font-size: 8.5px;
        font-weight: 700;
        line-height: 1.35;
        color: #4B5563;
    }
    .cert-official-header__email span {
        font-size: 9.5px;
        font-weight: 900;
    }
    .cert-official-header__logo {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cert-official-header__logo img {
        width: 32mm;
        height: 32mm;
        object-fit: contain;
    }
    .cert-official-header__logo-placeholder {
        width: 32mm;
        height: 32mm;
        border: 2px solid var(--bleu);
        border-radius: 999px;
        color: var(--bleu);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 24px;
        font-weight: 900;
    }
    .cert-official-header__agreements {
        margin: 2px auto 8px;
        max-width: 150mm;
        text-align: center;
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 9.5px;
        font-style: italic;
        font-weight: 900;
        line-height: 1.25;
        color: var(--bleu);
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
        font-size: 16px;
        font-weight: 900;
        line-height: 1.15;
        text-transform: uppercase;
        margin-top: 10px;
        color: var(--bleu-dark);
    }

    /* Bottom section - bilan, appreciations, conduct */
    .bilan-bottom {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin-top: 8px;
        margin-bottom: 8px;
        font-size: 8px;
    }
    .bilan-bottom-full {
        grid-column: 1 / -1;
    }
    .bottom-box {
        border: 1px solid #CBD5E1;
        border-radius: 4px;
        overflow: hidden;
    }
    .bottom-box-title {
        background: var(--bleu);
        color: white;
        font-size: 7px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: 3px 8px;
        text-align: center;
    }
    .bottom-box-content {
        padding: 5px 8px;
        min-height: 28px;
        font-size: 7.5px;
        font-weight: 600;
        color: #374151;
    }
    .appr-codes-row {
        display: flex;
        border: 1px solid #CBD5E1;
        border-radius: 4px;
        overflow: hidden;
        font-size: 6.5px;
        text-align: center;
        margin-bottom: 4px;
    }
    .appr-code-cell {
        flex: 1;
        padding: 3px 2px;
        border-right: 1px solid #CBD5E1;
        font-weight: 700;
    }
    .appr-code-cell:last-child { border-right: none; }
    .appr-code-cell .code { font-weight: 900; font-size: 8px; color: var(--bleu-dark); }
    .appr-code-cell .meaning { font-size: 5.5px; color: #6B7280; font-style: italic; line-height: 1.2; }
    .conduct-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7px;
    }
    .conduct-table th {
        background: #F3F4F6;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 6px;
        padding: 3px 6px;
        border: 1px solid #E5E7EB;
        text-align: center;
    }
    .conduct-table td {
        padding: 4px 6px;
        border: 1px solid #E5E7EB;
        text-align: center;
    }
    .work-badges {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        padding: 5px 8px;
        font-size: 6.5px;
    }
    .work-badge {
        border: 1px solid #CBD5E1;
        border-radius: 3px;
        padding: 2px 5px;
        text-align: center;
        flex: 1;
    }
    .work-badge .wbfr { font-weight: 700; font-size: 7px; color: #111827; }
    .work-badge .wben { font-size: 5.5px; color: #9CA3AF; font-style: italic; }
    .stats-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 6px;
        margin-bottom: 8px;
    }
    .stat-cell {
        border: 1px solid #CBD5E1;
        border-radius: 4px;
        padding: 4px 8px;
        text-align: center;
        font-size: 7.5px;
    }
    .stat-cell .slabel { font-size: 6px; font-weight: 700; color: #6B7280; text-transform: uppercase; }
    .stat-cell .svalue { font-size: 11px; font-weight: 900; color: var(--bleu-dark); margin-top: 2px; }
    .stat-cell .smeta { font-size: 6px; color: #9CA3AF; }
    .prev-trims {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4px;
        margin-top: 4px;
    }
    .prev-trim-cell {
        border: 1px solid #CBD5E1;
        border-radius: 3px;
        padding: 3px 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 7px;
    }
    .prev-trim-cell .ptlabel { font-weight: 700; color: #6B7280; }
    .prev-trim-cell .ptvalue { font-weight: 900; color: var(--bleu-dark); }
</style>
