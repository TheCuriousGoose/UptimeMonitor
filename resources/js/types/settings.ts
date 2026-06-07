export interface Setting {
    key: string;
    label: string;
    type: string;
    value: any;
}   

export interface Settings {
    authentication: Setting[]
}