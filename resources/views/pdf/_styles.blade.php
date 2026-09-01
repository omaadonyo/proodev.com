<style>
    @page { margin: 14mm 14mm 20mm 14mm; }
    /* NOTE: do not reset * margin here - dompdf derives page margins from the
       root frame, so `* { margin: 0 }` pushes content edge-to-edge. */
    * { padding: 0; box-sizing: border-box; }
    body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #1f2430; font-size: 9pt; line-height: 1.45; }

    /* Fixed footer: repeated on every page with page numbers */
    .page-footer {
        position: fixed;
        bottom: -16mm;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 7pt;
        color: #9ca3af;
    }
    .page-footer .brand-line { color: #6b7280; font-weight: bold; }
    .page-footer .page-num::after { content: "Page " counter(page) " of " counter(pages); }

    /* Document header */
    .header { width: 100%; border-bottom: 2pt solid #111827; padding-bottom: 3.5mm; }
    .brand-logo { width: 31mm; height: 7mm; }
    .brand { font-size: 15pt; font-weight: bold; letter-spacing: 0.5pt; color: #111827; }
    .brand .accent { color: #4f46e5; }
    .seller { margin-top: 1.8mm; font-size: 7pt; color: #6b7280; line-height: 1.45; }
    .doc-label { color: #4f46e5; font-size: 7.5pt; letter-spacing: 2.5px; text-transform: uppercase; font-weight: bold; text-align: right; }
    .doc-title { font-size: 15pt; font-weight: bold; color: #111827; text-align: right; margin-top: 1mm; }
    .doc-sub { font-size: 8pt; color: #6b7280; text-align: right; margin-top: 0.8mm; }

    /* Meta / info blocks */
    .meta { width: 100%; margin-top: 7mm; }
    .meta td { vertical-align: top; padding-right: 8mm; }
    .label { font-size: 6.8pt; color: #6b7280; text-transform: uppercase; letter-spacing: 1.1px; font-weight: bold; margin-bottom: 1.5mm; }
    .value { font-size: 9.5pt; color: #1f2430; }
    .value strong { font-weight: bold; color: #111827; }
    .muted { color: #6b7280; font-size: 8pt; }

    /* Summary cards */
    .summary { width: 100%; margin-top: 6mm; border-collapse: collapse; }
    .summary td { width: 25%; padding-right: 3.5mm; vertical-align: top; }
    .summary .box { border: 1px solid #e5e7eb; border-top: 2pt solid #4f46e5; background: #fcfcfd; padding: 2.8mm; page-break-inside: avoid; }
    .summary .value { font-size: 11pt; margin-top: 1mm; }
    .summary .sub { display: block; font-size: 6.8pt; color: #6b7280; font-weight: normal; margin-top: 0.6mm; }

    /* Section headers */
    .section { margin-top: 7mm; font-size: 9.5pt; font-weight: bold; color: #111827; border-bottom: 1pt solid #111827; padding-bottom: 1.6mm; }

    /* Data tables */
    table.items { width: 100%; border-collapse: collapse; margin-top: 3mm; font-size: 7.5pt; }
    table.items thead { display: table-header-group; }
    table.items th { background: #111827; color: #ffffff; font-size: 6.5pt; text-transform: uppercase; letter-spacing: 0.8px; padding: 2.2mm 2mm; text-align: left; }
    table.items th.amt, table.items td.amt { text-align: right; }
    table.items td { padding: 2.2mm 2mm; border-bottom: 1px solid #eef0f3; color: #374151; }
    table.items tr { page-break-inside: avoid; }
    table.items tr.alt td { background: #fafbfc; }
    table.items .mono { font-family: "Courier New", monospace; font-size: 7pt; }

    /* Status badges */
    .badge { font-size: 6pt; font-weight: bold; text-transform: uppercase; padding: 0.8mm 2mm; border-radius: 5mm; letter-spacing: 0.5px; }
    .badge.paid { background: #ecfdf5; color: #059669; }
    .badge.pending { background: #fffbeb; color: #b45309; }
    .badge.refunded { background: #fef2f2; color: #b91c1c; }
    .badge.cancelled { background: #f3f4f6; color: #6b7280; }

    /* Totals panel */
    table.totals { width: 100%; margin-top: 4mm; page-break-inside: avoid; }
    table.totals td { padding: 1.3mm 2mm; font-size: 9pt; color: #4b5563; }
    table.totals td.amt { text-align: right; font-weight: bold; color: #1f2430; width: 40mm; }
    table.totals tr.grand td { border-top: 1.5pt solid #111827; padding-top: 2mm; font-size: 11pt; font-weight: bold; color: #111827; }

    /* Accent info box */
    .info-box { width: 100%; margin-top: 6mm; border: 1px solid #e5e7eb; border-left: 2.5pt solid #4f46e5; background: #fcfcfd; padding: 3mm; page-break-inside: avoid; }
    .info-box td { padding: 1.2mm 3mm 1.2mm 0; font-size: 8pt; vertical-align: top; }
    .info-box .label { margin-bottom: 0.6mm; }
    .info-box .value { font-size: 8.5pt; font-weight: bold; color: #111827; }

    .notes { margin-top: 6mm; font-size: 8.5pt; color: #4b5563; }
    .notes strong { color: #111827; }
    .empty { margin-top: 7mm; text-align: center; color: #9ca3af; font-size: 10pt; }
</style>
