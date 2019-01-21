<template>
    <div class="editable-select" v-if="model.isEditing">
        <md-field>
            <md-select md-dense v-model="value" @md-selected="onSelect($event)">
                <md-option :value="item.id" v-for="item in data">
                    {{ item.name }}
                </md-option>
            </md-select>
        </md-field>
    </div>
    <div :class="{'cl-item': !related}" v-else>
        {{ related ? related.name : 'Item doesn\'t exist' }}
    </div>
</template>

<script lang="ts">
    import { Component, Vue, Prop } from 'vue-property-decorator';


    @Component
    export default class EditableSelect extends Vue {
        @Prop() data: {};
        @Prop() related: {
            id: number,
            name: string,
        } | null;
        @Prop() model: any;
        @Prop() name: string;

        value: number;

        onSelect(value) {
            this.$set(this.model, this.name, value);
        }

        mounted() {
            this.value = this.model[this.name];
        }
    }
</script>

<style lang="scss">
    .editable-select {
        max-width: 300px;

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

