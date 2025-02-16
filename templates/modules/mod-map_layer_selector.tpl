{tikimodule error=$module_params.error title=$tpl_module_title name="map_layer_selector" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    <form class="map-layer-selector" method="post" action="">
        {if !empty($controls.baselayer)}
            <select name="baseLayers">
            </select>
        {/if}

        {if !empty($controls.optionallayers)}
            <div class="optionalLayers">
            </div>
        {/if}
    </form>
    {jq}
    $('.map-layer-selector').hide();
    $(function () {
        $('.map-container').one('initialized', function () {
            $('.map-layer-selector').removeClass('map-layer-selector').each(function () {
                var refreshLayers, map = $(this).closest('.tab, #appframe, body').find('.map-container').first()
                    , baseLayers = $(this.baseLayers)
                    , optionalLayers = $('.optionalLayers', tr(this)) /* e.g. tr('Editable') to be translatable via lang/../language.js */
                    ;

                if (! map) {
                    return;
                }

                $(this).show();

                baseLayers.on("change", function () {
                    if (map.map) {
                        var layer = map.map.layers[$(this).val()];
                        map.map.setBaseLayer(layer);
                        if (layer.isBlank) {
                            layer.setVisibility(false);
                        }
                    }
                });

                refreshLayers = function () {
                    baseLayers.empty();
                    optionalLayers.empty();
                    $.each(map.map.layers, function (k, thisLayer) {
                        if (! thisLayer.displayInLayerSwitcher) {
                            return;
                        }

                        if (thisLayer.isBaseLayer) {
                            baseLayers.append($('<option/>')
                                .attr('value', k)
                                .text(tr(thisLayer.name))
                                .prop('selected', thisLayer === map.map.baseLayer));
                        } else {
                            var label, checkbox;
                            optionalLayers.append(label = $('<label/>').text(thisLayer.name).prepend(
                                checkbox = $('<input type="checkbox" class="form-check-input"/>')
                                    .prop('checked', thisLayer.getVisibility())));
                            checkbox.on("change", function (e) {
                                thisLayer.setVisibility($(this).is(':checked'));
                            });
                        }
                    });
                };

                refreshLayers();
            });
        });
    });
    {/jq}
{/tikimodule}
