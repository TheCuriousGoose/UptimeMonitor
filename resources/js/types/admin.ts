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

export type AppSetting = {
    id: number;
    key: string;
    group: string;
    label: string;
    description: string | null;
    type: 'string' | 'boolean' | 'integer' | 'float' | 'json';
    value: string | null;
};
