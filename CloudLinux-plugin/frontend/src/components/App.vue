<template>
    <div class="cl-whmcs">
        <md-tabs md-sync-route>
            <md-tab md-label="Licenses List" to="/" />
            <md-tab md-label="Addon Licenses List" to="/addons-list" />
            <md-tab md-label="Addon Relations" to="/addon-relations" />
            <md-tab md-label="Product Relations" to="/product-relations" />
            <md-tab md-label="Configurable Options Relations" to="/option-relations" />
        </md-tabs>
        <div>
            <loader ref="loader" />
            <notifications group="service" position="top center" />
            <router-view />
        </div>
    </div>
</template>

<script lang="ts">
    import { Vue, Component } from 'vue-property-decorator'
    import Loader from 'components/loader/Loader.vue';

    @Component({
        components: {
            Loader,
        }
    })
    export default class App extends Vue {
    }
</script>

<style lang="scss">
    @import url('https://fonts.googleapis.com/icon?family=Material+Icons');

    // Global styles
    .cl-whmcs {
        position: relative;
        min-width: 900px;
    }

    .cl-bold {
        font-weight: bold;
    }

    .cl-item {
        color: #ae432e;
    }

    .actions {
        display: flex;
    }

    .md-tabs {
        .md-active {
            &:focus, &:hover {
                text-decoration: none;
            }
        }
    }

    .md-table {
        .md-table-content {
            .md-empty-state-label {
                font-size: 18px;
            }
        }
    }

    a.md-button {
        &:focus, &:hover {
             text-decoration: none;
        }
    }

    button.md-button {
        .md-ripple {
            padding: 0 24px;
        }

        &.md-icon-button .md-ripple {
            padding: 0 8px;
        }
    }

    .md-field {
        label {
            font-size: 13px;
            font-weight: normal;
        }

        &.md-focused label, &.md-has-value label {
            font-size: 11px;
        }
    }

    .top-toolbar {
        position: relative;
        display: flex;
        margin: 10px auto;

        .md-title {
            margin: 0;

            span {
                display: block;
                font-size: 13px;
                font-style: italic;
            }
        }

        .md-button {
            margin-right: 0;
        }

        // TODO: move to component
        .add-form {
            position: absolute;
            border: solid 1px #c6c8cc;
            box-shadow: 0 2px 14px 0 rgba(0, 0, 0, 0.24), 0 0 2px 0 rgba(0, 0, 0, 0.12);
            z-index: 10;
            background-color: #fff;
            top: 0;
            right: 5px;
            min-width: 300px;
            padding: 15px 10px;

            .md-field {
                padding: 0;
                min-height: 28px;

                .md-input {
                    font-size: 15px;
                }
            }

            .form-item {
                label {
                    font-size: 14px;
                    font-weight: normal;
                    color: #393838;
                }
            }

            .form-actions {
                display: flex;
                width: 100%;

                button {
                    width: 100%;
                    margin: 6px 0;

                    &:first-child {
                        margin-right: 3px;
                    }
                }
            }
        }
    }
</style>