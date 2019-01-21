<template>
    <div>
        <div class="top-toolbar">
            <div class="md-toolbar-section-start">
                <h1 class="md-title">
                    Existing Configurable Option Relations
                    <span>The following configuration allows you to pair license product with configurable options.</span>
                </h1>
            </div>

            <div class="md-toolbar-section-end">
                <md-button class="md-raised md-primary" @click="showAddForm=!showAddForm">Add relation</md-button>

                <div class="add-form" v-if="showAddForm">
                    <form novalidate @submit.prevent="validateForm">
                        <div class="form-item">
                            <label for="product_id">License Product</label>
                            <md-field :class="getFormValidationClass('product_id')">
                                <md-select md-dense v-model="form.product_id" name="product_id"
                                           id="product_id" placeholder="Select Product...">
                                    <md-option :value="item.id" v-for="item in formItems.clProducts">
                                        {{ item.name }}
                                    </md-option>
                                </md-select>
                                <span class="md-error" v-if="!$v.form.product_id.required">The Product is required</span>
                            </md-field>
                        </div>
                        <div class="form-item">
                            <label for="option_group_id">Linked ConfigurableOption Group</label>
                            <md-field :class="getFormValidationClass('option_group_id')">
                                <md-select md-dense v-model="form.option_group_id" name="option_group_id"
                                           id="option_group_id" placeholder="Select Group..."
                                           @md-selected="form.option_id=null">
                                    <md-option :value="item.id" v-for="item in formItems.optionGroups">
                                        {{ item.name }}
                                    </md-option>
                                </md-select>
                                <span class="md-error" v-if="!$v.form.option_group_id.required">The Group is required</span>
                            </md-field>
                        </div>
                        <div class="form-item" v-if="form.option_group_id">
                            <label for="option_id">Linked ConfigurableOption</label>
                            <md-field :class="getFormValidationClass('option_id')">
                                <md-select md-dense v-model="form.option_id" name="option_id"
                                           id="option_id" placeholder="Select Option...">
                                    <md-option :value="item.id" v-for="item in getGroupOptions(form.option_group_id)">
                                        {{ item.name }}
                                    </md-option>
                                </md-select>
                                <span class="md-error" v-if="!$v.form.option_id.required">The Option is required</span>
                            </md-field>
                        </div>

                        <div class="form-actions">
                            <md-button type="submit" class="md-raised md-primary">Add relation</md-button>
                            <md-button class="md-raised" @click="clearForm">Cancel</md-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <table-wrapper :actions="actions" with-actions="true">
            <template slot-scope="{ item }">
                <md-table-cell md-label="ID" md-sort-by="id">
                    {{ item.id }}
                </md-table-cell>
                <md-table-cell md-label="License Product" md-sort-by="product_id">
                    <editable-select :model="item" name="product_id" :related="item.package"
                                     :data="formItems.clProducts" />
                </md-table-cell>
                <md-table-cell md-label="Linked Configurable Option Group" md-sort-by="option_group_id">
                    <editable-select :model="item" name="option_group_id" :related="item.option_group"
                                     :data="formItems.optionGroups" />
                </md-table-cell>
                <md-table-cell md-label="Linked Configurable Option" md-sort-by="option_id">
                    <editable-select :model="item" name="option_id" :related="item.option"
                                     :data="getGroupOptions(item.option_group_id)" />
                </md-table-cell>
            </template>
        </table-wrapper>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Component from 'vue-class-component';
    import { validationMixin } from 'vuelidate'
    import { required } from 'vuelidate/lib/validators'
    import { Request } from 'utils/request';
    import useLoader from 'components/loader/loader';
    import { ActionModel } from 'utils/mixins';
    import TableWrapper from 'components/table/TableWrapper.vue';
    import { TableEvent } from 'components/table/TableWrapper.vue';
    import EditableSelect from 'components/table/EditableSelect.vue';


    interface OptionForm {
        product_id: number | null;
        option_id: number | null;
        option_group_id: number | null;
    }

    @Component({
        components: {
            TableWrapper,
            EditableSelect,
        },
        mixins: [
            validationMixin,
        ],
        validations: {
            form: {
                product_id: {
                    required,
                },
                option_id: {
                    required,
                },
                option_group_id: {
                    required,
                },
            },
        },
    })
    export default class ConfigurableOptionRelations extends Vue {
        actions: ActionModel = {
            get: 'getOptionRelationsList',
            add: 'addOptionRelation',
            edit: 'editOptionRelation',
            delete: 'deleteOptionRelation',
        };
        showAddForm: boolean = false;
        form: OptionForm = {
            product_id: null,
            option_id: null,
            option_group_id: null,
        };
        formItems: {
            clProducts: any[],
            optionGroups: any[];
        } = {
            clProducts: [],
            optionGroups: [],
        };

        @useLoader
        async loadFormItems() {
            let response = await Request.post({
                command: 'getFieldsData',
                params: {
                    withOptions: true,
                },
            });
            this.formItems = response.data;
        }

        // TODO: move form to single component
        async addItem() {
            let data = Object.assign({}, this.form);
            TableEvent.$emit('add-item', data);
            this.clearForm();
        }

        validateForm () {
            this.$v.$touch();

            if (!this.$v.$invalid) {
                this.addItem();
            }
        }

        getFormValidationClass (fieldName: string) {
            const field = this.$v.form[fieldName];

            if (field) {
                return {
                    'md-invalid': field.$invalid && field.$dirty
                };
            }
        }

        clearForm () {
            this.$v.$reset();
            this.form.option_group_id = null;
            this.form.option_id = null;
            this.form.product_id = null;
            this.showAddForm = false;
        }

        getGroupOptions(groupId: number) {
            if (this.formItems.optionGroups !== void 0) {
                let group = this.formItems.optionGroups.find(e => e.id === groupId);
                if (group) {
                    return group.options;
                }
            }

            return [];
        }

        async created() {
            await this.loadFormItems();
        }
    }
</script>
