<template>
    <div class="md-table-pagination" v-if="totalCount">
        <template v-if="pageOptions !== false">
            <span class="md-table-pagination-label">Rows per page:</span>

            <md-field>
                <md-select v-model="limit" md-dense md-class="md-pagination-select">
                    <md-option v-for="amount in pageOptions" :key="amount" :value="amount">{{ amount }}</md-option>
                </md-select>
            </md-field>
        </template>

        <span>Page {{ currentPage }} of {{ totalPages }} | Total: {{ totalCount }}</span>

        <md-button class="md-icon-button md-table-pagination-previous" @click="goToPrevious()"
                   :disabled="currentPage === 1">
            <md-icon>keyboard_arrow_left</md-icon>
        </md-button>

        <md-button class="md-icon-button md-table-pagination-next" @click="goToNext()"
                   :disabled="currentPage === totalPages">
            <md-icon>keyboard_arrow_right</md-icon>
        </md-button>
    </div>
</template>

<script lang="ts">
    import { Vue, Component, Prop, Watch } from 'vue-property-decorator';

    export interface PaginationParams {
        offset: number;
        limit: number;
    }

    @Component
    export default class Pagination extends Vue {
        @Prop({ default: 25 }) limit: number;
        @Prop({ default: () => [10, 25, 50, 100] }) pageOptions: number[];
        @Prop({ default: 0 }) totalCount: number;

        currentPage = 1;
        params: PaginationParams = {
            offset: 0,
            limit: this.limit,
        };

        @Watch('limit')
        onLimitChanged(value: number) {
            this.params.limit = value;
            this.goToPage(1);
        }

        @Watch('totalCount')
        onTotalCountChanged(value: number) {
            if (this.totalPages < this.currentPage) {
                this.goToPage(this.totalPages);
            }
        }

        get totalPages() {
            let count = Math.ceil(this.totalCount / this.limit);
            return count ? count : 1;
        }

        get offset(): number {
            return this.params.offset;
        }

        set offset(value: number) {
            this.params.offset = value;
            this.$router.push({query: {
                offset: `${this.params.offset}`,
            }});
        }

        goToPage(value: number) {
            if (value > this.totalPages || value < 1) {
                return;
            }
            this.offset = this.limit * (value - 1);
            this.currentPage = this.offset / this.limit + 1;
            this.$emit('update:pagination', this.params);
            this.$emit('pagination', this.params);
        }

        goToPrevious() {
            this.goToPage(this.currentPage - 1);
        }

        goToNext() {
            this.goToPage(this.currentPage + 1);
        }
    }
</script>

<style lang="scss">
    .md-table-pagination {
        height: 56px;
        display: flex;
        flex: 1;
        align-items: center;
        justify-content: flex-end;
        border-top: 1px solid;
        font-size: 12px;

        .md-table-pagination-previous {
            margin-right: 2px;
            margin-left: 18px;
        }

        .md-field {
            width: 48px;
            min-width: 36px;
            margin: -16px 24px 0 32px;

            &:after,
            &:before {
                display: none;
            }

            .md-select-value {
                font-size: 13px;
            }
        }
    }

    .md-menu-content.md-pagination-select {
        max-width: 82px;
        min-width: 56px;
        margin-top: 5px;
    }
</style>
