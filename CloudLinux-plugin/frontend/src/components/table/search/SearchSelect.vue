<template>
    <div class="search-item">
        <md-field>
            <label>{{placeholder}}</label>
            <md-select md-dense v-model="value" :name="name" @md-selected="addValue">
                <md-option value="">none</md-option>
                <md-option :value="item.id" v-for="item in data">
                    {{ item.name }}
                </md-option>
            </md-select>
        </md-field>
    </div>
</template>

<script lang="ts">
    import { Component, Vue, Prop } from 'vue-property-decorator';
    import { SearchEvent } from 'components/table/search/Search.vue';
    import { SearchData } from 'components/table/search/Search.vue';


    @Component
    export default class SearchSelect extends Vue {
        @Prop({default: 'Search by ...'}) placeholder: string;
        @Prop() name: string;
        @Prop() data: SearchData;

        value: any = void 0;

        clearValue() {
            SearchEvent.$emit('search-clear', this.name);
        }

        addValue() {
            if (this.value === void 0) {
                return;
            }
            SearchEvent.$emit('search-add', { [this.name]: this.value });
        }
    }
</script>

<style lang="scss">
    .search-item {
        .md-field {
            .md-select {
                .md-input {
                    font-size: 13px;
                    padding: 0 0 0 3px;
                }
            }
        }
    }
</style>
