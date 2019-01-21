<template>
    <div class="cl-loader" v-if="isLoading">
        <div>
            <md-progress-spinner :md-diameter="100"
                                 :md-stroke="10" md-mode="indeterminate"></md-progress-spinner>
        </div>
    </div>
</template>

<script lang="ts">
    import { Vue, Component } from 'vue-property-decorator';

    @Component
    export default class Loader extends Vue {
        isLoading: boolean = false;
    }
</script>

<style lang="scss" scoped>
    .cl-loader {
        position: absolute;
        top: 0;
        z-index: 100;
        background-color: #ffffff;
        width: 100%;
        height: 100%;
        opacity: .7;

        div {
            text-align: center;
            margin-top: 50px;
        }
    }
</style>