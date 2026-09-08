<?php

namespace Webkul\Bagisto\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CredentialDataGrid extends DataGrid
{
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('wk_bagisto_credential')
            ->select(
                'id',
                'shop_url',
                'email'
            );

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('bagisto::app.bagisto.credentials.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'shop_url',
            'label'      => trans('bagisto::app.bagisto.credentials.index.datagrid.shop-url'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'email',
            'label'      => trans('bagisto::app.bagisto.credentials.index.datagrid.email'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('bagisto.credentials.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('bagisto::app.bagisto.credentials.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.bagisto.credentials.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('bagisto.credentials.destroy')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('bagisto::app.bagisto.credentials.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.bagisto.credentials.destroy', $row->id);
                },
            ]);
        }
    }
}
