<template>
    <div>
        <div class="top-toolbar">
            <div class="md-toolbar-section-start">
                <h1 class="md-title">
                    Existing Product Relations
                    <span>The following configuration allows you to pair license product with other product.</span>
                </h1>
            </div>

            <div class="md-toolbar-section-end">
                <md-button class="md-raised md-primary" @click="showAddForm=!showAddForm">Add relation</md-button>

                <div class="add-form" v-if="showAddForm">
                    <form novalidate @submit.prevent="validateForm">
                        <div class="form-item">
                            <label for="non_cl_product_id">Main product</label>
                            <md-field :class="getFormValidationClass('non_cl_product_id')">
                                <md-select md-dense v-model="form.non_cl_product_id" name="non_cl_product_id"
                                           id="non_cl_product_id" placeholder="Select product...">
                                    <md-option :value="item.id" v-for="item in formItems.nonClProducts">
                                        {{ item.name }}
                                    </md-option>
                                </md-select>
                                <span class="md-error" v-if="!$v.form.non_cl_product_id.required">The Product Addon is required</span>
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
                <md-table-cell md-label="Main product" md-sort-by="freeProductID">
                    <editable-select :model="item" name="freeProductID" :related="item.non_cl_package"
                                  :data="formItems.nonClProducts" />
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
    import { Component, Vue } from 'vue-property-decorator';
    import { validationMixin } from 'vuelidate'
    import { required } from 'vuelidate/lib/validators'
    import EditableSelect from 'components/table/EditableSelect.vue';
    import { Request } from 'utils/request';
    import useLoader from 'components/loader/loader';
    import { ActionModel } from 'utils/mixins';
    import TableWrapper from 'components/table/TableWrapper.vue';
    import { TableEvent } from 'components/table/TableWrapper.vue';


    interface ProductForm {
        non_cl_product_id: number | null;
        product_id: number | null;
    }

    @Component({
        components: {
            EditableSelect,
            TableWrapper,
        },
        mixins: [
            validationMixin,
        ],
        validations: {
            form: {
                non_cl_product_id: {
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
            get: 'getProductRelationsList',
            add: 'addProductRelation',
            edit: 'editProductRelation',
            delete: 'deleteProductRelation',
        };

        showAddForm: boolean = false;
        form: ProductForm = {
            non_cl_product_id: null,
            product_id: null,
        };
        formItems: {
            clProducts: any[],
            nonClProducts: any[],
        } = {
            clProducts: [],
            nonClProducts: [],
        };

        @useLoader
        async loadFormItems() {
            let response = await Request.post({
                command: 'getFieldsData',
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
            this.form.non_cl_product_id = null;
            this.form.product_id = null;
            this.showAddForm = false;
        }

        async mounted() {
            await this.loadFormItems();
        }
    }
</script>
