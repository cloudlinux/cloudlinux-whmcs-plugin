<template>
    <div>
        <div class="top-toolbar">
            <div class="md-toolbar-section-start">
                <h1 class="md-title">Licenses List</h1>
            </div>
        </div>

        <table-wrapper :actions="actions">
            <search slot="search">
                <template slot-scope="data">
                    <search-input placeholder="Search by Client Name" name="client_name" />
                    <search-select placeholder="Search by Main Product" name="main_product" :data="data.allProducts"/>
                    <search-select placeholder="Search by License Product" name="license_product" :data="data.allProducts"/>
                    <search-input placeholder="Search by IP Address" name="license_ip" />
                    <search-input placeholder="Search by License Key" name="license_key" />
                </template>
            </search>

            <template slot-scope="{ item }">
                <md-table-cell slot="cell" md-label="ID" md-sort-by="id" md-numeric>{{ item.id }}</md-table-cell>
                <md-table-cell md-label="Client ID" md-sort-by="user_id">
                    <a :href="`clientssummary.php?userid=${item.user_id}`" target="_blank">#{{ item.user_id }}</a>
                </md-table-cell>
                <md-table-cell md-label="Main Product" md-sort-by="main_product">
                   {{ item.main_product_name }}
                </md-table-cell>
                <md-table-cell md-label="License Product" md-sort-by="license_product">
                    <a :href="`clientsservices.php?userid=${item.user_id}&id=${item.license_id}`"
                       target="_blank">{{ item.license_name }}</a>
                </md-table-cell>
                <md-table-cell md-label="IP Address \ License Key">
                    {{ item.license_item }}
                </md-table-cell>
                <md-table-cell md-label="License Type" md-sort-by="license_type" class="cl-item">
                    {{ item.license_type }}
                </md-table-cell>
            </template>
        </table-wrapper>
    </div>
</template>

<script lang="ts">
    import { Component, Vue } from 'vue-property-decorator';
    import Search from 'components/table/search/Search.vue';
    import SearchInput from 'components/table/search/SearchInput.vue';
    import SearchSelect from 'components/table/search/SearchSelect.vue';
    import TableWrapper from 'components/table/TableWrapper.vue';
    import { ActionModel } from 'utils/mixins';


    @Component({
        components: {
            Search,
            SearchInput,
            SearchSelect,
            TableWrapper,
        },
    })
    export default class LicensesList extends Vue {
        actions: ActionModel = {
            get: 'getLicenseList',
        };
    }
</script>
