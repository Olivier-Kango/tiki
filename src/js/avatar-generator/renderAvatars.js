import { createAvatar } from "@dicebear/core";
import * as collections from "@dicebear/collection";
import avatarSizeConstant from "./avatarSize.constant";

export default function renderAvatars() {
    $(".dicebear-avatar").each(function () {
        const style = collections[$(this).data("style")];
        if (!style) {
            return;
        }
        const avatar = createAvatar(style, { seed: $(this).data("seed"), size: avatarSizeConstant });
        const image = $("<img>").attr("src", avatar.toDataUri());
        $(this).replaceWith(image);
    });
}
