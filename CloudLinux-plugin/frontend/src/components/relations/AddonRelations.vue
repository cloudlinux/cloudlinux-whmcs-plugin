<template>
    <div>
        <div class="top-toolbar">
            <div class="md-toolbar-section-start">
                <h1 class="md-title">
                    Existing Addon Relations
                    <span>The following configuration allows you to pair license product with addons.</span>
                    <span>Note: Since WHMCS version 7.2 you can use addon provisioning options instead.</span>
                </h1>
            </div>

            <div class="md-toolbar-section-end">
                <md-button class="md-raised md-primary" @click="showAddForm=!showAddForm">Add relation</md-button>

                <div class="add-form" v-if="showAddForm">
                    <form novalidate @submit.prevent="validateForm">
                        <div class="form-item">
                            <label for="addon_id">Product Addon</label>
                            <md-field :class="getFormValidationClass('addon_id')">
                                <md-select md-dense v-model="form.addon_id" name="addon_id"
                                           id="addon_id" placeholder="Select addon...">
                                    <md-option :value="item.id" v-for="item in formItems.addons">
                                        {{ item.name }}
                                    </md-option>
                                </md-select>
                                <span class="md-error" v-if="!$v.form.addon_id.required">The Product Addon is required</span>
                            </md-field>
                        </div>
                        <div class="form-item">
                            <label for="product_id">Linked Product With License</label>
                            <md-field :class="getFormValidationClass('product_id')">
                                <md-select md-dense v-model="form.product_id" name="product_id"
                                           id="product_id" placeholder="Select product...">
                                    <md-option :value="item.id" v-for="item in formItems.clProducts">
                                        {{ item.name }}
                                    </md-option>
                                </md-select>
                                <span class="md-error" v-if="!$v.form.product_id.required">The Product is required</span>
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
                <md-table-cell md-label="Product Addon" md-sort-by="addonID">
                    <editable-select :model="item" name="addonID" :related="item.addon"
                                     :data="formItems.addons" />
                </md-table-cell>
                <md-table-cell md-label="Linked License Product" md-sort-by="licenseProductID">
                    <editable-select :model="item" name="licenseProductID" :related="item.package"
                                     :data="formItems.clProducts" />
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


    interface AddonForm {
        addon_id: number | null;
        product_id: number | null;
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
                addon_id: {
                    required,
                },
                product_id: {
                    required,
                },
            },
        },
    })
    export default class AddonRelations extends Vue {
        actions: ActionModel = {
            get: 'getAddonRelationsList',
            add: 'addAddonRelation',
            edit: 'editAddonRelation',
            delete: 'deleteAddonRelation',
        };
        showAddForm: boolean = false;
        form: AddonForm = {
            addon_id: null,
            product_id: null,
        };
        formItems: {
            clProducts: any[],
            addons: any[];
        } = {
            clProducts: [],
            addons: [],
        };

        @useLoader
        async loadFormItems() {
            let response = await Request.post({
                command: 'getFieldsData',
                params: {
                    withAddons: true,
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
            this.form.addon_id = null;
            this.form.product_id = null;
            this.showAddForm = false;
        }

        async mounted() {
            await this.loadFormItems();
        }
    }
</script>
