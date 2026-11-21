<?php
/** SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later */
declare(strict_types=1);
return ['routes'=>[
    ['name'=>'page#index','url'=>'/spaces/{spaceId}','verb'=>'GET'],
    ['name'=>'draft#index','url'=>'/api/spaces/{spaceId}/drafts','verb'=>'GET'],
    ['name'=>'draft#create','url'=>'/api/spaces/{spaceId}/drafts','verb'=>'POST'],
]];
