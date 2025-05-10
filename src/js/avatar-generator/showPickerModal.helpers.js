import { createAvatar } from "@dicebear/core";
import * as collections from "@dicebear/collection";
import avatarSizeConstant from "./avatarSize.constant";

export function getRandomAvatars(activeStyle) {
    const avatars = [];
    for (let i = 0; i < 24; i++) {
        const seed = Math.random().toString(36).substring(2, 15);
        const avatar = createAvatar(collections[activeStyle], { seed, size: avatarSizeConstant });
        avatars.push({ seed, svg: avatar.toString() });
    }
    return avatars;
}

export function getAvatarElement(avatar) {
    return `
        <div class="avatar-picker__avatars__item bg-secondary-subtle border rounded border-3 border-white" data-seed="${avatar.seed}" role="button">
            ${avatar.svg}
        </div>
    `;
}

export function handleAvatarClick(container) {
    const avatarItems = $(container).find(".avatar-picker__avatars__item");
    avatarItems.on("click", function () {
        avatarItems.removeClass("border-success");
        avatarItems.addClass("border-white");

        $(this).removeClass("border-white");
        $(this).addClass("border-success");
    });
}
