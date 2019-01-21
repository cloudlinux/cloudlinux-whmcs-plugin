<template>
    <div class="search-form">
        <slot v-bind="data" />
    </div>
</template>

<script lang="ts">
    import { Component, Vue } from 'vue-property-decorator';
    import { Request } from 'utils/request';
    import useLoader from 'components/loader/loader';

    export const SearchEvent = new Vue();

    export interface SearchData {
        clProducts: object[],
        nonClProducts: object[],
        allProducts: object[],
        addons?: object[],
    }

    @Component
    export default class Search extends Vue {
        data: SearchData = {
            clProducts: [],
            nonClProducts: [],
            allProducts: [],
        };
        search: {} = {};

        onSearch(data: {}) {
            this.search = {...this.search, ...data};
            SearchEvent.$emit('search', this.search);
        }

        onClearSearch(attribute: string) {
            delete this.search[attribute];
            SearchEvent.$emit('search', this.search);
        }

        @useLoader
        async load() {
            let response = await Request.post({
                command: 'getFieldsData',
            });
            this.data = response.data;
        }

        mounted() {
            this.load();

            SearchEvent.$on('search-clear', this.onClearSearch);
            SearchEvent.$on('search-add', this.onSearch);
        }

        destroyed() {
            SearchEvent.$off('search-clear');
            SearchEvent.$off('search-add');
        }
    }
</script>

<style lang="scss">
    .search-form {
        margin-bottom: 10px;

        .search-item {
            max-width: 250px;
            min-width: 150px;
            margin: 6px;
            display: inline-block;

            .md-field {
                margin: 0 auto;
            }
        }
    }
</style>