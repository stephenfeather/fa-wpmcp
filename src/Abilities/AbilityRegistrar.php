<?php

/**
 * Ability registrar - registers all abilities with the AbilityRegistry.
 *
 * @package FAWpmcp\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities;

/**
 * Handles registration of all abilities with the AbilityRegistry.
 *
 * This class is extracted from Plugin.php to reduce class method count
 * and improve single responsibility principle compliance.
 *
 * @package FAWpmcp\Abilities
 */
final class AbilityRegistrar
{
    /**
     * Register all abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    public static function registerAll(AbilityRegistry $registry): void
    {
        self::registerPostAbilities($registry);
        self::registerCommentAbilities($registry);
        self::registerMediaAbilities($registry);
        self::registerTaxonomyAbilities($registry);
        self::registerPostTypeAbilities($registry);
        self::registerUserAbilities($registry);
        self::registerSettingsAbilities($registry);
        self::registerPluginAbilities($registry);
        self::registerThemeAbilities($registry);
        self::registerPrivacyAbilities($registry);
        self::registerCacheAbilities($registry);
        self::registerMaintenanceAbilities($registry);
        self::registerTransientAbilities($registry);
        self::registerCronAbilities($registry);
        self::registerRoleAbilities($registry);
        self::registerMenuAbilities($registry);
        self::registerWidgetAbilities($registry);
        self::registerDotenvAbilities($registry);
        self::registerCoreAbilities($registry);
        self::registerRewriteAbilities($registry);
        self::registerConfigAbilities($registry);
    }

    /**
     * Register Post abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerPostAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Posts\GetPost());
        $registry->register(new \FAWpmcp\Abilities\Posts\ListPosts());
        $registry->register(new \FAWpmcp\Abilities\Posts\CreatePost());
        $registry->register(new \FAWpmcp\Abilities\Posts\UpdatePost());
        $registry->register(new \FAWpmcp\Abilities\Posts\DeletePost());
    }

    /**
     * Register Comment abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerCommentAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Comments\GetComment());
        $registry->register(new \FAWpmcp\Abilities\Comments\ListComments());
        $registry->register(new \FAWpmcp\Abilities\Comments\CreateComment());
        $registry->register(new \FAWpmcp\Abilities\Comments\UpdateComment());
        $registry->register(new \FAWpmcp\Abilities\Comments\DeleteComment());
    }

    /**
     * Register Media abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerMediaAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Media\GetMedia());
        $registry->register(new \FAWpmcp\Abilities\Media\ListMedia());
        $registry->register(new \FAWpmcp\Abilities\Media\UploadMedia());
        $registry->register(new \FAWpmcp\Abilities\Media\UpdateMedia());
        $registry->register(new \FAWpmcp\Abilities\Media\DeleteMedia());
    }

    /**
     * Register Taxonomy abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerTaxonomyAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Taxonomies\GetTerm());
        $registry->register(new \FAWpmcp\Abilities\Taxonomies\ListTerms());
        $registry->register(new \FAWpmcp\Abilities\Taxonomies\CreateTerm());
        $registry->register(new \FAWpmcp\Abilities\Taxonomies\UpdateTerm());
        $registry->register(new \FAWpmcp\Abilities\Taxonomies\DeleteTerm());
        $registry->register(new \FAWpmcp\Abilities\Taxonomies\ListTaxonomies());
        $registry->register(new \FAWpmcp\Abilities\Taxonomies\GetTaxonomy());
    }

    /**
     * Register Post Type abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerPostTypeAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\PostTypes\ListPostTypes());
        $registry->register(new \FAWpmcp\Abilities\PostTypes\GetPostType());
    }

    /**
     * Register User abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerUserAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Users\GetUser());
        $registry->register(new \FAWpmcp\Abilities\Users\ListUsers());
        $registry->register(new \FAWpmcp\Abilities\Users\CreateUser());
        $registry->register(new \FAWpmcp\Abilities\Users\UpdateUser());
        $registry->register(new \FAWpmcp\Abilities\Users\DeleteUser());
    }

    /**
     * Register Settings abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerSettingsAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Settings\DeleteOption());
        $registry->register(new \FAWpmcp\Abilities\Settings\GetOption());
        $registry->register(new \FAWpmcp\Abilities\Settings\ListOptions());
        $registry->register(new \FAWpmcp\Abilities\Settings\UpdateOption());
    }

    /**
     * Register Plugin abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerPluginAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Plugins\ActivatePlugin());
        $registry->register(new \FAWpmcp\Abilities\Plugins\DeactivatePlugin());
        $registry->register(new \FAWpmcp\Abilities\Plugins\DeletePlugin());
        $registry->register(new \FAWpmcp\Abilities\Plugins\GetPlugin());
        $registry->register(new \FAWpmcp\Abilities\Plugins\InstallPlugin());
        $registry->register(new \FAWpmcp\Abilities\Plugins\ListPlugins());
        $registry->register(new \FAWpmcp\Abilities\Plugins\UpdatePlugin());
    }

    /**
     * Register Theme abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerThemeAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Themes\ActivateTheme());
        $registry->register(new \FAWpmcp\Abilities\Themes\DeleteTheme());
        $registry->register(new \FAWpmcp\Abilities\Themes\GetTheme());
        $registry->register(new \FAWpmcp\Abilities\Themes\InstallTheme());
        $registry->register(new \FAWpmcp\Abilities\Themes\ListThemes());
        $registry->register(new \FAWpmcp\Abilities\Themes\StatusTheme());
        $registry->register(new \FAWpmcp\Abilities\Themes\UpdateTheme());
    }

    /**
     * Register Privacy abilities with the registry.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerPrivacyAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Privacy\CreateErasureRequest());
        $registry->register(new \FAWpmcp\Abilities\Privacy\CreateExportRequest());
        $registry->register(new \FAWpmcp\Abilities\Privacy\GetPrivacyRequest());
        $registry->register(new \FAWpmcp\Abilities\Privacy\ListPrivacyRequests());
    }

    /**
     * Register cache abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerCacheAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Cache\FlushCache());
        $registry->register(new \FAWpmcp\Abilities\Cache\GetCacheStatus());
        $registry->register(new \FAWpmcp\Abilities\Cache\GetCacheType());
    }

    /**
     * Register maintenance abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerMaintenanceAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Maintenance\ActivateMaintenanceMode());
        $registry->register(new \FAWpmcp\Abilities\Maintenance\DeactivateMaintenanceMode());
        $registry->register(new \FAWpmcp\Abilities\Maintenance\GetMaintenanceModeStatus());
    }

    /**
     * Register transient abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerTransientAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Transients\GetTransient());
        $registry->register(new \FAWpmcp\Abilities\Transients\ListTransients());
        $registry->register(new \FAWpmcp\Abilities\Transients\SetTransient());
        $registry->register(new \FAWpmcp\Abilities\Transients\DeleteTransient());
    }

    /**
     * Register cron abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerCronAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Cron\ListCronEventsAbility());
        $registry->register(new \FAWpmcp\Abilities\Cron\GetCronEventAbility());
        $registry->register(new \FAWpmcp\Abilities\Cron\ScheduleCronEventAbility());
        $registry->register(new \FAWpmcp\Abilities\Cron\UnscheduleCronEventAbility());
        $registry->register(new \FAWpmcp\Abilities\Cron\RunCronEventAbility());
        $registry->register(new \FAWpmcp\Abilities\Cron\ListCronSchedulesAbility());
    }

    /**
     * Register role abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerRoleAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Role\ListRolesAbility());
        $registry->register(new \FAWpmcp\Abilities\Role\GetRoleAbility());
        $registry->register(new \FAWpmcp\Abilities\Role\CreateRoleAbility());
        $registry->register(new \FAWpmcp\Abilities\Role\UpdateRoleAbility());
        $registry->register(new \FAWpmcp\Abilities\Role\DeleteRoleAbility());
        $registry->register(new \FAWpmcp\Abilities\Role\ListCapsAbility());
        $registry->register(new \FAWpmcp\Abilities\Role\AddCapAbility());
        $registry->register(new \FAWpmcp\Abilities\Role\RemoveCapAbility());
    }

    /**
     * Register menu abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerMenuAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Menu\ListMenusAbility());
        $registry->register(new \FAWpmcp\Abilities\Menu\GetMenuAbility());
        $registry->register(new \FAWpmcp\Abilities\Menu\CreateMenuAbility());
        $registry->register(new \FAWpmcp\Abilities\Menu\DeleteMenuAbility());
    }

    /**
     * Register widget abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerWidgetAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Widgets\ListSidebarsAbility());
        $registry->register(new \FAWpmcp\Abilities\Widgets\GetSidebarAbility());
        $registry->register(new \FAWpmcp\Abilities\Widgets\ListWidgetTypesAbility());
        $registry->register(new \FAWpmcp\Abilities\Widgets\ListWidgetsAbility());
        $registry->register(new \FAWpmcp\Abilities\Widgets\GetWidgetAbility());
        $registry->register(new \FAWpmcp\Abilities\Widgets\AddWidgetAbility());
        $registry->register(new \FAWpmcp\Abilities\Widgets\UpdateWidgetAbility());
        $registry->register(new \FAWpmcp\Abilities\Widgets\DeleteWidgetAbility());
        $registry->register(new \FAWpmcp\Abilities\Widgets\MoveWidgetAbility());
        $registry->register(new \FAWpmcp\Abilities\Widgets\ResetWidgetsAbility());
    }

    /**
     * Register dotenv abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerDotenvAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Dotenv\ListEnvVarsAbility());
        $registry->register(new \FAWpmcp\Abilities\Dotenv\GetEnvVarAbility());
        $registry->register(new \FAWpmcp\Abilities\Dotenv\SetEnvVarAbility());
        $registry->register(new \FAWpmcp\Abilities\Dotenv\DeleteEnvVarAbility());
    }

    /**
     * Register core abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerCoreAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Core\GetCoreVersionAbility());
        $registry->register(new \FAWpmcp\Abilities\Core\CheckCoreUpdatesAbility());
        $registry->register(new \FAWpmcp\Abilities\Core\VerifyChecksumsAbility());
        $registry->register(new \FAWpmcp\Abilities\Core\IsInstalledAbility());
        $registry->register(new \FAWpmcp\Abilities\Core\UpdateDatabaseAbility());
    }

    /**
     * Register rewrite abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerRewriteAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Rewrite\ListRewriteRulesAbility());
        $registry->register(new \FAWpmcp\Abilities\Rewrite\FlushRewriteRulesAbility());
        $registry->register(new \FAWpmcp\Abilities\Rewrite\GetPermalinkStructureAbility());
        $registry->register(new \FAWpmcp\Abilities\Rewrite\UpdatePermalinkStructureAbility());
    }

    /**
     * Register config abilities.
     *
     * @param AbilityRegistry $registry Ability registry.
     * @return void
     */
    private static function registerConfigAbilities(AbilityRegistry $registry): void
    {
        $registry->register(new \FAWpmcp\Abilities\Config\ListConfigConstantsAbility());
        $registry->register(new \FAWpmcp\Abilities\Config\GetConfigConstantAbility());
    }
}
