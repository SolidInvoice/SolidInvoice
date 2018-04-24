<template>
    <div class="panel panel-default">
        <div class="panel-heading" v-if="actions">
            <h3 class="panel-title">
		        <span class="actions">
                    <span v-for="(action, actionName) in actions" :key="actionName">
                        <button type="submit" :class="'btn btn-' + action.className + ' btn-xs'">
                            <i :class="'fa fa-' + action.icon"></i>
                            {{action.label}}
                        </button>
                        &nbsp;
                    </span>
                </span>
            </h3>
        </div>

        <table class="table table-hover table-bordered" v-if="rows.length > 0" role="grid">
            <thead>
            <tr>
                <th v-if="lineActions" style="width: 1%"><input v-model="allCheckBox" type="checkbox" @change="selectAll"></th>
                <th v-for="column in columns" :key="column.name">
                    {{ column.label }}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="item in rows" :key="item.id">
                <td v-if="lineActions"><input :value="item.id" v-model="selected" type="checkbox"></td>
                <td v-for="column in columns" :key="column.name" v-html="format(column.cell, item[column.name])">
                </td>
                <td v-if="lineActions">
                    <div class="btn-group btn-group-xs dropdown">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            ...
                        </button>
                        <ul class="dropdown-menu">
                            <li v-for="action in lineActions">
                                <a :href="route(action, item)"> <!-- @TODO: Use router -->
                                    <i :class="'fa fa-' + action.icon" v-if="action.icon"></i>
                                    {{ action.label }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
    //import jQuery from 'jquery'
    import {formatter} from '../js/CellFormatter'
    import {keys, map, pick, result, values, zipObject} from 'lodash'
    import Router from '@SolidInvoiceCoreBundle/Resources/assets/js/router'

    export default {
        props: {
            columns: {type: Array, required: true},
            actions: {type: Object, required: false},
            name: {type: String, required: true},
            lineActions: {type: Object, required: false},
            properties: {type: Object, required: true}
        },
        data() {
            return {
                rows: [],
                selected: [],
                allCheckBox: false,
                page: 1,
                perPage: 15,
                sort: 'created', // Always default to created
                order: 'desc' // Default newest first
            };
        },
        methods: {
            format(type, value) {
                return formatter.get(type).format(value);
            },
            selectAll() {
                this.selected = this.allCheckBox ? map(this.rows, 'id') : [];
            },
            route(action, item) {
                return Router.generate(action.route, zipObject(values(action.route_params), values(pick(item, keys(action.route_params)))));
            },
            fetchData: function() {
                let routeParams = {name: this.name};

                if (this.properties.paginate) {
                    routeParams.page = this.page;
                    routeParams.per_page = this.perPage;
                }

                if (this.properties.sortable) {
                    routeParams.sort = this.sort;
                    routeParams.order = this.order;
                }

                // @TODO: use Axios for Ajax requests
                /*jQuery.ajax({
                    url: Router.generate('_grid_data', routeParams)
                }).then((result) => {
                    this.rows = result.items;
                });*/
            }
        },
        created() {
            this.fetchData();
        }
    };
</script>

<style scoped lang="less">
    /*.dropdown {
        &:hover {
            .dropdown-menu {
                display: block;
            }
        }

        &-menu:hover{
            display: block;
        }
    }*/
</style>