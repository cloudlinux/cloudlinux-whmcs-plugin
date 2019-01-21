<template>
    <div class="search-item">
        <md-field md-clearable @md-clear="clearValue" class="md-toolbar-section-end">
            <label>{{placeholder}}</label>
            <md-input v-model="value" @keyup.enter="addValue" />
        </md-field>
    </div>
</template>

<script lang="ts">
    import { Component, Vue, Prop } from 'vue-property-decorator';
    import { SearchEvent } from 'components/table/search/Search.vue';


    @Component
    export default class SearchInput extends Vue {
        @Prop({default: 'Search by ...'}) placeholder: string;
        @Prop() name: string;

        value: any = null;

        clearValue() {
            SearchEvent.$emit('search-clear', this.name);
        }

        addValue() {
            SearchEvent.$emit('search-add', { [this.name]: this.value });
        }
    }
</script>

<style lang="scss" scoped>
    .md-field {
        .md-input {
            font-size: 13px;
            padding: 0 0 0 3px;
        }
    }
</style>
