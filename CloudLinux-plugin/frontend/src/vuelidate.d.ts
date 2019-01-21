// FIXEME: https://github.com/monterail/vuelidate/issues/175

import Vue, { PluginFunction } from 'vue'

export as namespace vuelidate;

import { Params, ValidationRule, ValidationParams } from 'vuelidate/lib/validators'
export { Params, ValidationRule, ValidationParams } from 'vuelidate/lib/validators'

/**
 * Covers beforeCreate(), beforeDestroy() and data().
 *
 * No public members.
 */
export const validationMixin: any

// const Validation
export interface Validation extends Vue {
    // const validationGetters
    readonly $invalid: boolean
    readonly $dirty: boolean
    readonly $error: boolean
    readonly $pending: boolean
    readonly $params: { [attr: string]: any }

    // const validationMethods
    $touch(): never
    $reset(): never
    $flattenParams(): ValidationParams[]
}

// pre-defined rules
export function required(): ValidationRule
export function requiredIf(field: string): ValidationRule
export function requiredUnless(field: string): ValidationRule
export function minLength(length: number): ValidationRule
export function maxLength(length: number): ValidationRule
export function minValue(min: number): ValidationRule
export function maxValue(max: number): ValidationRule
export function between(min: number, max: number): ValidationRule
export function alpha(): ValidationRule
export function alphaNum(): ValidationRule
export function numeric(): ValidationRule
export function email(): ValidationRule
export function ipAddress(): ValidationRule
export function macAddress(): ValidationRule
export function sameAs(field: string): ValidationRule
export function url(): ValidationRule
export function or(...validators: ValidationRule[]): ValidationRule
export function and(...validators: ValidationRule[]): ValidationRule

// vue plugin
export const Vuelidate: PluginFunction<any>

export default Vuelidate

// vue augmentation

type ValidationDecl = ValidationRule | ((...args: any[]) => ValidationRule)

interface FlatValidationDecl {
    [attr: string]: ValidationDecl
}

interface NamedValidationDecl {
    [rule: string]: ValidationDecl | FlatValidationDecl | NamedValidationDecl
}

interface NestedValidationDecl {
    [attr: string]: ValidationDecl | FlatValidationDecl | NamedValidationDecl | NestedValidationDecl
}

interface ValidationGroupDecl {
    validationGroup?: string[]
}

type ValidationDecls = ValidationDecl | FlatValidationDecl | NamedValidationDecl | NestedValidationDecl | ValidationGroupDecl

declare module 'vue/types/vue' {
    interface Vue {
        $v: { [attr: string]: Validation } & Validation

        delayTouch(v: Validation): void

        validations(): ValidationDecls
    }
}

declare module 'vue/types/options' {
    interface ComponentOptions<V extends Vue> {
        validations?: ValidationDecls
    }
}
