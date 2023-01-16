<template>
  <div class="card">
    <div class="card-header">
      <div class="row">
        <div class="col-6">
          <span class="text-left">
            <i :class="`fas fa-${icon}`" /> {{ title }}
          </span>
        </div>
        <div class="col-6">
          <div class="text-right">
            <slot name="header" />
          </div>
        </div>
      </div>
    </div>
    <div class="card-body">
      <button
        v-for="(action, index) in actions"
        :key="index"
        type="submit"
        :class="`btn btn-${action.className} btn-xs mx-1 my-2`"
      >
        <i :class="`fas fa-${action.icon}`" />
        {{ Translator.trans(action.label) }}
      </button>

      <div class="row my-2">
        <div class="col-sm-6 col-md-2">
          <b-form-select
            v-model="perPage"
            size="sm"
            :options="[5, 10, 15, 20, 30, 50]"
          />
        </div>
        <div class="offset-6 col-md-4 text-right">
          <div
            id="dataTable_filter"
            class="dataTables_filter"
          >
            <b-input-group class="mb-2 mr-sm-2 mb-sm-0">
              <template #prepend>
                <div class="input-group-text">
                  <i class="fas fa-search" />
                </div>
              </template>
              <template #default>
                <b-form-input
                  v-model.lazy="filter"
                  type="search"
                  placeholder="Search"
                />
              </template>
            </b-input-group>
          </div>
        </div>
      </div>

      <b-table
        ref="table"
        v-model:busy="isBusy"
        primary-key="id"
        bordered
        outlined
        hover
        selectable
        show-empty
        responsive
        sort-icon-left
        :items="items"
        :fields="fields"
        :filter="filter"
        :per-page="perPage"
        :current-page="currentPage"
        @row-selected="onRowSelected"
      >
        <template #head(selected)>
          <b-form-checkbox
            v-model="selectAllChecked"
            @change="toggleSelectAll"
          />
        </template>
        <template #cell(selected)="{ index, rowSelected }">
          <b-form-checkbox
            :checked="rowSelected"
            @change="selectRow($event, index)"
          />
        </template>
        <template #cell(actions)="field">
          <b-button
            v-for="(action, index) in lineActions"
            :key="index"
            :href="rowAction(field, action)"
            size="sm"
            class="mx-1"
            :class="action.className"
            :title="Translator.trans(action.label)"
          >
            <i :class="`fas fa-${action.icon}`" />
            {{ Translator.trans(action.label) }}
          </b-button>
        </template>
        <template #empty>
          <div class="text-center text-info my-2">
            <h4>No {{ title }} found</h4>
          </div>
        </template>
        <template #table-busy>
          <div class="text-center text-info my-2">
            <b-spinner class="align-middle mx-2" />
            <strong>Loading...</strong>
          </div>
        </template>
      </b-table>
      <div class="row">
        <div class="col-6">
          <span class="justify-content-start">
            Showing {{ recordsShown.start }} to {{ recordsShown.total }} of {{ totalRows }} records
          </span>
        </div>
        <div class="col-6">
          <b-pagination
            v-model="currentPage"
            align="right"
            :total-rows="totalRows"
            :per-page="perPage"
            aria-controls="my-table"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
    import { $fetch } from 'ohmyfetch';
    import {
        BButton,
        BFormCheckbox,
        BFormInput,
        BFormSelect,
        BInputGroup,
        BPagination,
        BSpinner,
        BTable
    } from 'bootstrap-vue';
    // @ts-ignore
    import Router from 'router';
    // @ts-ignore
    import Translator from 'translator';
    import { computed, Ref, ref } from 'vue';
    import formatter from '../../../util/grid/formatter';

    const props = defineProps({
        name: {
            type: String,
            required: true,
        },
        title: {
            type: String,
            required: true,
        },
        icon: {
            type: String,
            required: true,
        },
        columns: {
            type: Array,
            required: true,
        },
        actions: {
            type: Object,
            required: false,
            default: () => {},
        },
        lineActions: {
            type: Object,
            required: false,
            default: () => {},
        },
    });

    const isBusy = ref(false);
    const currentPage = ref(1);
    const perPage = ref(15);
    const totalRows = ref(0);
    const selectAllChecked = ref(false);
    const filter = ref(null);

    declare type Table = {
        selectAllRows: () => void,
        clearSelected: () => void,
        selectRow: (index: number) => void,
        unselectRow: (index: number) => void,
    };

    const table: Ref<Table | null> = ref(null);

    const rowAction = (field: any, action: any) => {
        const params: any = {};

        if (action.route_params) {
            Object.keys(action.route_params).forEach((param) => {
                params[param] = field.item[action.route_params[param]];
            });
        }

        return Router.generate(action.route, params);
    };

    const recordsShown = computed(() => {
        let start = (currentPage.value * perPage.value) - perPage.value + 1;
        let total = currentPage.value * perPage.value;

        if (total > totalRows.value) {
            total = totalRows.value;
        }

        if (start > totalRows.value) {
            start = totalRows.value;
        }

        return { start, total };
    });

    const toggleSelectAll = (checked: boolean) => {
        if (checked) {
            table.value?.selectAllRows();
        } else {
            table.value?.clearSelected();
        }
    };

    const onRowSelected = (selectedRows: string[]) => {
        selectAllChecked.value = selectedRows.length === perPage.value;
    };

    const selectRow = (checked: boolean, index: number) => {
        if (checked) {
            table.value?.selectRow(index);
        } else {
            table.value?.unselectRow(index);
        }
    };

    const fields = [
        'selected',
    ];

    // @ts-ignore
    fields.push(...props.columns?.map((column: {
        key: string,
        label: string,
        sortable: boolean,
        cell: string,
    }): {} => ({
        key: column.key,
        label: column.label,
        sortable: column.sortable,
        formatter: formatter[column.cell] ?? null,
    })));

    fields.push('actions');

    const items = async (ctx: {
        currentPage: string,
        perPage: number,
        sortDesc: string,
        sortBy: string,
        filter: string,
    }) => {
        isBusy.value = true;

        const parameters = {
            page: ctx.currentPage,
            per_page: ctx.perPage,
            name: props.name,
            order: ctx.sortDesc ? 'desc' : 'asc',
            sort: ctx.sortBy,
            q: ctx.filter,
        };

        const results = await $fetch(Router.generate('_grid_data', parameters));

        totalRows.value = results.count;

        isBusy.value = false;

        return results.items;
    };
</script>
