<template>
    <div>
        <slot name="search" />

        <md-table v-model="items" :md-sort.sync="sort.name"
                  :md-sort-order.sync="sort.order"
                  :md-sort-fn="customSort" md-card>
            <md-table-empty-state md-label="No records found" />

            <md-table-row slot="md-table-row" slot-scope="{ item }">
                <slot :item="item" />

                <md-table-cell v-if="withActions">
                    <div class="actions">
                        <div v-if="item.isEditing">
                            <md-button class="md-raised md-primary" @click="editItem(item)">
                                Save
                            </md-button>
                            <md-button class="md-raised" @click="toggleEditing(item)">
                                Cancel
                            </md-button>
                        </div>
                        <md-button class="md-icon-button" v-else @click="toggleEditing(item)">
                            <md-icon>edit</md-icon>
                        </md-button>
                        <md-button class="md-icon-button" @click="toggleDeleting(item)">
                            <md-icon>delete</md-icon>
                        </md-button>
                    </div>
                </md-table-cell>
            </md-table-row>
        </md-table>

        <pagination :totalCount="totalCount" :pagination.sync="pagination" @pagination="onPagination" />

        <md-dialog-confirm :md-active.sync="showConfirmDialog" md-title="Remove item?"
               md-confirm-text="OK, Remove item" md-cancel-text="CANCEL"
               @md-cancel="toggleDeleting" @md-confirm="deleteItem()" />
    </div>
</template>

<script lang="ts">
    import { Vue, Component, Prop } from 'vue-property-decorator';
    import { Request }  from 'utils/request';
    import Pagination from 'components/table/Pagination.vue';
    import { PaginationParams } from 'components/table/Pagination.vue';
    import useLoader from 'components/loader/loader';
    import { SortModel, ActionModel } from 'utils/mixins';
    import { SearchEvent } from 'components/table/search/Search.vue';


    export const TableEvent = new Vue();

    @Component({
        components: {
            Pagination,
        }
    })
    export default class TableWrapper extends Vue {
        @Prop() actions: ActionModel;
        @Prop({default: false}) withActions: boolean;

        sort: SortModel = {
            name: 'id',
            order: 'asc',
        };
        pagination: PaginationParams;
        search: {} = {};
        searchEvent: Vue = new Vue();

        data: any[] = [];
        items: any[] = [];
        totalCount: number = 0;

        showConfirmDialog: boolean = false;
        itemForDelete: any;

        @useLoader
        async load(params?: object) {
            params = {
                sort: this.sort,
                pagination: this.pagination,
                search: this.search,
            };
            let response = await Request.post({
                command: this.actions.get,
                params
            });
            this.items = response.data.items;
            this.totalCount = response.data.totalCount;
        }

        @useLoader
        async editItem(item: any) {
            const response = await Request.post({
                command: this.actions.edit,
                params: item,
            });
            this.toggleEditing(item);
            this.notify(response.message);
            await this.load();
        }

        @useLoader
        async addItem(item: any) {
            const response = await Request.post({
                command: this.actions.add,
                params: item,
            });
            this.notify(response.message);
            await this.load();
        }

        toggleDeleting(item: null) {
            this.showConfirmDialog = !this.showConfirmDialog;
            this.itemForDelete = item;
        }

        @useLoader
        async deleteItem() {
            const response = await Request.post({
                command: this.actions.delete,
                params: this.itemForDelete,
            });
            this.notify(response.message);
            await this.load();
        }

        async onSearch(data: {}) {
            this.search = data;
            await this.load();
        }

        async customSort() {
            await this.load();
        }

        async onPagination() {
            await this.load();
        }

        toggleEditing(item) {
            this.$set(item, 'isEditing', !item.isEditing);
        }

        async mounted() {
            await this.load();
            SearchEvent.$on('search', this.onSearch);
            TableEvent.$on('add-item', this.addItem);
        }

        destroyed() {
            SearchEvent.$off('search');
            TableEvent.$off('add-item');
        }

        private notify(message: string) {
            this.$notify({
                group: 'service',
                type: 'success',
                title: 'Success',
                text: message,
            });
        }
    }
</script>

<style lang="scss">
    .md-table-cell-container {
        padding-right: 8px;
    }
    .md-table-cell:last-child .md-table-cell-container {
        padding-right: 8px;
    }
</style>