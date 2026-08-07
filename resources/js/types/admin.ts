export type Permission = {
    id: number;
    name: string;
};

export type Role = {
    id: number;
    name: string;
    permissions: Permission[];
    users_count?: number;
};

export type AppSettingType =
    | 'string'
    | 'boolean'
    | 'integer'
    | 'float'
    | 'json'
    | 'secret';

export type AppSetting = {
    id: number;
    key: string;
    group: string;
    parent_key: string | null;
    label: string;
    description: string | null;
    type: AppSettingType;
    value: string | null;
    has_value: boolean;
    children?: AppSetting[];
};
