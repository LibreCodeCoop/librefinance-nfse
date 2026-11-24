<?php
/**
 * SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\LibreFinanceNfse\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Pre-release consolidated baseline. Creates the complete schema on a fresh
 * Nextcloud installation and safely completes known development schemas.
 *
 * Older migration files are intentionally superseded; existing financial
 * records are never dropped, truncated or replaced.
 */
final class Version000100Date20261121190000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        // Consolidated development schema: Version000001Date20261120220000.php
        if(!$schema->hasTable('lfn_drafts')){
            $t=$schema->createTable('lfn_drafts');
            $t->addColumn('id','integer',['notnull'=>true,'autoincrement'=>true]);
            $t->addColumn('space_id','integer',['notnull'=>true]);
            $t->addColumn('operation_key','string',['notnull'=>true,'length'=>64]);
            $t->addColumn('request_hash','string',['notnull'=>true,'length'=>64]);
            $t->addColumn('issuer_cnpj','string',['notnull'=>true,'length'=>14]);
            $t->addColumn('municipality_code','string',['notnull'=>true,'length'=>7]);
            $t->addColumn('competence','string',['notnull'=>true,'length'=>10]);
            $t->addColumn('service_code','string',['notnull'=>true,'length'=>15]);
            $t->addColumn('description','string',['notnull'=>true,'length'=>255]);
            $t->addColumn('amount_minor','bigint',['notnull'=>true]);
            $t->addColumn('status','string',['notnull'=>true,'length'=>20]);
            $t->addColumn('created_by','string',['notnull'=>true,'length'=>64]);
            $t->addColumn('created_at','integer',['notnull'=>true]);

            $t->addColumn('company_id', 'integer', ['notnull' => false]);
            $t->setPrimaryKey(['id']);
            $t->addUniqueIndex(['space_id','operation_key'],'lfn_sp_operation');
            $t->addIndex(['space_id','id'],'lfn_sp_id');

            $t->addIndex(['space_id', 'company_id'], 'lfn_sp_company');}

        // Consolidated development schema: Version000002Date20261121123000.php
        if ($schema->hasTable('lfn_drafts')) {
            $table = $schema->getTable('lfn_drafts');
            if (!$table->hasColumn('company_id')) {
                $table->addColumn('company_id', 'integer', ['notnull' => false]);
                $table->addIndex(['space_id', 'company_id'], 'lfn_sp_company');
            }
        }

        return $schema;
    }
}
