export interface EditableModel {
    isEditing: boolean,
}

export interface SortModel {
    name: string;
    order: string;
}

export interface ActionModel {
    get: string;
    add?: string;
    edit?: string;
    delete?: string;
}
