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

/** Preserve legacy drafts while allowing explicit fiscal issuer linkage. */
final class Version000002Date20261121123000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
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
