<?php

namespace App\Livewire\Concerns;

use App\Exports\GenericRowsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Flux\Flux;
use Maatwebsite\Excel\Facades\Excel;

trait ExportsSelectedRows
{
    public array $selectedIds = [];

    public function toggleSelectAll(): void
    {
        $allIds = $this->selectableIds();

        if ($allIds !== [] && count($this->selectedIds) === count($allIds)) {
            $this->selectedIds = [];
        } else {
            $this->selectedIds = $allIds;
        }
    }

    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function exportSelectedPdf()
    {
        if ($this->selectedIds === []) {
            Flux::toast(variant: 'warning', text: 'Select at least one row to export.');

            return;
        }

        [$headings, $rows] = $this->exportData();

        $pdf = Pdf::loadView('pdf.rows-export', [
            'title' => $this->exportTitle(),
            'headings' => $headings,
            'rows' => $rows,
        ])->setPaper('a4')->setOption('defaultFont', 'Helvetica');

        $filename = $this->exportBasename().'-'.count($this->selectedIds).'-rows-'.now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function exportSelectedExcel()
    {
        if ($this->selectedIds === []) {
            Flux::toast(variant: 'warning', text: 'Select at least one row to export.');

            return;
        }

        [$headings, $rows] = $this->exportData();

        return Excel::download(
            new GenericRowsExport($headings, $rows),
            $this->exportBasename().'-'.count($this->selectedIds).'-rows-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * IDs of the rows currently visible on the page (used by select-all).
     *
     * @return array<int, int>
     */
    abstract protected function selectableIds(): array;

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, string|int|float|null>>}
     */
    abstract protected function exportData(): array;

    abstract protected function exportTitle(): string;

    abstract protected function exportBasename(): string;
}
