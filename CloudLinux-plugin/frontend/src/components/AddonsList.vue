<template>
    <div>
        <div class="top-toolbar">
            <div class="md-toolbar-section-start">
                <h1 class="md-title">Addon Licenses List</h1>
            </div>
        </div>

        <table-wrapper :actions="actions">
            <search slot="search" withAddons="true">
                <template slot-scope="data">
                    <search-input placeholder="Search by Client Name" name="client_name" />
                    <search-select placeholder="Search by Addon" name="addon_id" :data="data.addons"/>
                    <search-input placeholder="Search by IP/License Key" name="license_item" />
                </template>
            </search>

            <template slot-scope="{ item }">
                <md-table-cell md-label="ID" md-sort-by="id" md-numeric>{{ item.id }}</md-table-cell>
                <md-table-cell md-label="Client ID" md-sort-by="user_id" md-numeric>
                    <a :href="`clientssummary.php?userid=${item.user_id}`" target="_blank">#{{ item.user_id }}</a>
                </md-table-cell>
                <md-table-cell md-label="Client Name" md-sort-by="client_name">
                    <a :href="`clientssummary.php?userid=${item.user_id}`"
                       class="cl-bold" target="_blank">{{ item.client_name }}</a>
                </md-table-cell>
                <md-table-cell md-label="Addon Name" md-sort-by="addon_name">
                    <a :href="`clientsservices.php?userid=${item.user_id}&id=${item.hosting_id}&aid=${item.id}`"
                       target="_blank">{{ item.addon_name }}</a>
                </md-table-cell>
                <md-table-cell md-label="IP Address \ License Key" md-sort-by="license_item">
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
            get: 'getAddonList',
        };
    }
</script>
