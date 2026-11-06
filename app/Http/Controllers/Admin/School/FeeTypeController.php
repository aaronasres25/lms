<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Admin\MasterController;
use App\Models\FeeType;
use Illuminate\Database\Eloquent\Model;

class FeeTypeController extends MasterController
{
    protected function modelClass(): string { return FeeType::class; }
    protected function title(): string { return 'Fee type'; }
    protected function routePrefix(): string { return 'admin.school.fee-types'; }
    protected function permResource(): string { return 'fees'; }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name (Tuition)', 'type' => 'text', 'required' => true],
            ['name' => 'amount', 'label' => 'Default amount', 'type' => 'number', 'required' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'text'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'amount' => ['required', 'numeric', 'min:0'], 'description' => ['nullable', 'string', 'max:255']];
    }

    protected function extraColumns(): array { return ['Amount' => fn ($r) => number_format($r->amount, 2), 'Description' => fn ($r) => $r->description]; }
}
