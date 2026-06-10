import { createMongoAbility } from '@casl/ability';

export type Actions = 'create' | 'read' | 'update' | 'delete' | 'manage' | string

export type Subjects = 'Post' | 'Comment' | 'all' | string

export interface Rule { action: Actions; subject: Subjects }

export const ability = createMongoAbility<[Actions, Subjects]>()
