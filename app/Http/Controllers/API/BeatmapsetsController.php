<?php

/**
 *    Copyright 2015 ppy Pty. Ltd.
 *
 *    This file is part of osu!web. osu!web is distributed with the hope of
 *    attracting more community contributions to the core ecosystem of osu!.
 *
 *    osu!web is free software: you can redistribute it and/or modify
 *    it under the terms of the Affero GNU General Public License version 3
 *    as published by the Free Software Foundation.
 *
 *    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
 *    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *    See the GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace App\Http\Controllers\API;

use Authorizer;
use App\Transformers\BeatmapsetTransformer;
use App\Models\User;

class BeatmapsetsController extends Controller
{
    public function favourites()
    {
        $current_user = User::find(Authorizer::getResourceOwnerId());
        $favourites = $current_user->favouriteBeatmapsets();

        return fractal_api_serialize_collection(
            $favourites->get(),
            new BeatmapsetTransformer()
        );
    }
}
