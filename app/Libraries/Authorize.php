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
namespace App\Libraries;

use Auth;
use App\Exceptions\AuthorizationException;

class Authorize
{
    public static function canUser($user, $ability, $args)
    {
        if (!is_array($args)) {
            $args = [$args];
        }

        $function = "static::check{$ability}";

        // null means authorized
        $message = call_user_func_array(
            $function, array_merge([$user], $args)
        );

        // FIXME: add generic error message if blank
        if ($message !== null) {
            if ($message === '') {
                $message = '';
            } else {
                $message = "authorization.{$message}";
            }
        }

        return [$message === null, trans($message)];
    }

    public static function can($ability, $args)
    {
        return static::canUser(Auth::user(), $ability, $args);
    }

    public static function ensureCanUser($user, $ability, $args)
    {
        $can = static::canUser($user, $ability, $args);

        if (!$can[0]) {
            throw new AuthorizationException($can[1]);
        }
    }

    public static function ensureCan($ability, $args)
    {
        return static::ensureCanUser(Auth::user(), $ability, $args);
    }

    public static function checkForumView($user, $forum)
    {
        if ($user !== null && $user->is_admin) {
            return;
        }

        if ($forum->categoryId() === config('osu.forum.admin_forum_id')) {
            return 'forum.view.admin_only';
        }
    }

    public static function checkForumPostDelete($user, $post, $positionCheck = true, $position = null, $topicPostsCount = null)
    {
        $prefix = 'forum.post.delete';

        if ($user === null) {
            return "{$prefix}.require_login";
        }

        if ($user->is_admin) {
            return;
        }

        if ($post->poster_id !== $user->user_id) {
            return "{$prefix}.not_post_owner";
        }

        if ($positionCheck === false) {
            return;
        }

        if ($position === null) {
            $position = $post->postPosition;
        }

        if ($topicPostsCount === null) {
            $topicPostsCount = $post->topic->postsCount();
        }

        if ($position !== $topicPostsCount) {
            return "{$prefix}.can_only_delete_last_post";
        }
    }

    public static function checkForumPostEdit($user, $post)
    {
        $prefix = 'forum.post.edit';

        if ($user === null) {
            return  "{$prefix}.require_login";
        }

        if ($user->is_admin) {
            return;
        }

        if ($post->poster_id !== $user->user_id) {
            return "{$prefix}.not_post_owner";
        }

        if ($post->post_edit_locked) {
            return "{$prefix}.locked";
        }
    }

    public static function checkForumTopicEdit($user, $topic)
    {
        return static::checkForumPostEdit($user, $topic->posts()->first());
    }

    public static function checkForumTopicCoverEdit($user, $cover)
    {
        $prefix = 'forum.topic_cover.edit';

        if ($cover->topic !== null) {
            return static::checkForumTopicEdit($user, $cover->topic);
        }

        if ($user === null) {
            return "{$prefix}.require_login";
        }

        if ($user->is_admin) {
            return;
        }

        if ($cover->owner() === null) {
            return "{$prefix}.uneditable";
        }

        if ($cover->owner()->user_id !== $user->user_id) {
            return "{$prefix}.owner_only";
        }
    }
}
