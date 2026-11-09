<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Folder,
    LayoutGrid,
    Tags,
    Upload,
    UserRound,
} from '@lucide/vue';
import AuthorController from '@/actions/App/Http/Controllers/AuthorController';
import BookController from '@/actions/App/Http/Controllers/BookController';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import CollectionController from '@/actions/App/Http/Controllers/CollectionController';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as inventoryImport } from '@/routes/inventory-import';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Books',
        href: BookController.index.url(),
        icon: BookOpen,
    },
    {
        title: 'Import inventory',
        href: inventoryImport.url(),
        icon: Upload,
    },
    {
        title: 'Authors',
        href: AuthorController.index.url(),
        icon: UserRound,
    },
    {
        title: 'Categories',
        href: CategoryController.index.url(),
        icon: Tags,
    },
    {
        title: 'Collections',
        href: CollectionController.index.url(),
        icon: Folder,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
