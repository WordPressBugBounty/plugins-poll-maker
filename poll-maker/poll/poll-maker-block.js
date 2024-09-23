(function(wp) {
    let el = wp.element.createElement,
        registerBlockType = wp.blocks.registerBlockType,
        withSelect = wp.data.withSelect,
        BlockControls = wp.editor.BlockControls,
        AlignmentToolbar = wp.editor.AlignmentToolbar,
        InspectorControls = wp.blocks.InspectorControls,
        ServerSideRender = wp.components.ServerSideRender,
        __ = wp.i18n.__,
        Text = wp.components.TextControl,
        aysSelect = wp.components.SelectControl;    

    var iconEl = el(
        'svg', 
        { 
            xmlns: 'http://www.w3.org/2000/svg',
            width: 50,
            height: 50,
            viewBox: '0 0 130 130'
        },
        el(
            'g',
            el(
                'path',
                { 
                    d: "M 3.077 3.077 L 0 6.154 -0 63.677 L -0 121.200 3.400 124.600 L 6.800 128 64.055 128 L 121.310 128 125.155 123.958 L 129 119.916 128.921 62.208 C 128.878 30.469, 128.636 5.001, 128.384 5.612 C 128.123 6.243, 126.470 5.270, 124.562 3.362 L 121.200 0 63.677 0 L 6.154 0 3.077 3.077 M 0.468 63.500 C 0.468 95.400, 0.595 108.595, 0.749 92.821 C 0.904 77.048, 0.904 50.948, 0.750 34.821 C 0.595 18.695, 0.468 31.600, 0.468 63.500 M 34.229 19.669 C 33.530 19.951, 32.292 21.468, 31.479 23.041 C 29.835 26.220, 29.481 46.159, 30.658 69.250 L 31.309 82 39.654 82 L 48 82 48 50.500 L 48 19 41.750 19.079 C 38.313 19.122, 34.928 19.388, 34.229 19.669 M 80.750 27.571 C 80.338 27.990, 80 40.408, 80 55.167 L 80 82 89 82 L 98 82 98 57.556 C 98 33.342, 97.978 33.086, 95.651 30.380 C 93.699 28.111, 92.304 27.578, 87.401 27.229 C 84.155 26.998, 81.162 27.151, 80.750 27.571 M 56.557 39.223 C 55.272 41.057, 55 44.988, 55 61.723 L 55 82 63.500 82 L 72 82 72 60.700 C 72 45.467, 71.658 39.058, 70.800 38.200 C 70.140 37.540, 67.016 37, 63.857 37 C 59.055 37, 57.858 37.365, 56.557 39.223 M 36.228 87.287 C 33.127 88.704, 32.287 92.555, 34.536 95.040 C 36.673 97.402, 40.278 97.579, 42.429 95.429 C 46.456 91.402, 41.491 84.883, 36.228 87.287 M 86.223 87.557 C 83.774 89.272, 83.211 93.811, 85.200 95.800 C 88.911 99.511, 96.477 94.629, 94.048 90.090 C 93.136 88.385, 90.068 86, 88.788 86 C 88.599 86, 87.445 86.701, 86.223 87.557 M 58.993 89.853 C 55.508 93.338, 55.225 95.791, 57.966 98.733 C 60.851 101.830, 63.707 102.422, 66.603 100.525 C 72.504 96.658, 71.235 89.680, 64.243 87.549 C 62.523 87.024, 61.273 87.573, 58.993 89.853 M 31.155 100.393 C 30.096 101.552, 28.923 103.512, 28.548 104.749 C 27.695 107.562, 30.873 109, 37.940 109 C 49.129 109, 51.370 106.970, 46.531 101.220 C 44.353 98.630, 43.418 98.286, 38.571 98.286 C 34.263 98.286, 32.666 98.740, 31.155 100.393 M 81.923 100.077 C 79.956 102.044, 78.361 106.695, 79.324 107.657 C 80.398 108.731, 92.515 109.207, 95.932 108.310 C 100.301 107.162, 100.583 106.162, 97.734 101.932 C 95.571 98.722, 95.046 98.490, 89.609 98.352 C 85.125 98.238, 83.367 98.633, 81.923 100.077 M 53.803 105.961 C 50.780 108.985, 49.933 110.551, 50.179 112.664 C 50.449 114.981, 51.139 115.560, 54.500 116.292 C 59.447 117.370, 74.819 116.581, 76.222 115.178 C 77.873 113.527, 76.162 108.962, 72.531 105.331 C 69.537 102.337, 68.888 102.092, 66.422 103.030 C 64.518 103.754, 62.748 103.759, 60.706 103.047 C 57.993 102.101, 57.423 102.342, 53.803 105.961",
                    fill: '#3c444b'
                }
            ),
            el(
                'path',
                { 
                    d: "M 3.077 3.077 L 0 6.154 -0 63.677 L -0 121.200 3.400 124.600 L 6.800 128 64.055 128 L 121.310 128 125.155 123.958 L 129 119.916 128.921 62.208 C 128.878 30.469, 128.636 5.001, 128.384 5.612 C 128.123 6.243, 126.470 5.270, 124.562 3.362 L 121.200 0 63.677 0 L 6.154 0 3.077 3.077 M 0.468 63.500 C 0.468 95.400, 0.595 108.595, 0.749 92.821 C 0.904 77.048, 0.904 50.948, 0.750 34.821 C 0.595 18.695, 0.468 31.600, 0.468 63.500 M 34.229 19.669 C 33.530 19.951, 32.292 21.468, 31.479 23.041 C 29.835 26.220, 29.481 46.159, 30.658 69.250 L 31.309 82 39.654 82 L 48 82 48 50.500 L 48 19 41.750 19.079 C 38.313 19.122, 34.928 19.388, 34.229 19.669 M 80.750 27.571 C 80.338 27.990, 80 40.408, 80 55.167 L 80 82 89 82 L 98 82 98 57.556 C 98 33.342, 97.978 33.086, 95.651 30.380 C 93.699 28.111, 92.304 27.578, 87.401 27.229 C 84.155 26.998, 81.162 27.151, 80.750 27.571 M 56.557 39.223 C 55.272 41.057, 55 44.988, 55 61.723 L 55 82 63.500 82 L 72 82 72 60.700 C 72 45.467, 71.658 39.058, 70.800 38.200 C 70.140 37.540, 67.016 37, 63.857 37 C 59.055 37, 57.858 37.365, 56.557 39.223 M 36.228 87.287 C 33.127 88.704, 32.287 92.555, 34.536 95.040 C 36.673 97.402, 40.278 97.579, 42.429 95.429 C 46.456 91.402, 41.491 84.883, 36.228 87.287 M 86.223 87.557 C 83.774 89.272, 83.211 93.811, 85.200 95.800 C 88.911 99.511, 96.477 94.629, 94.048 90.090 C 93.136 88.385, 90.068 86, 88.788 86 C 88.599 86, 87.445 86.701, 86.223 87.557 M 58.993 89.853 C 55.508 93.338, 55.225 95.791, 57.966 98.733 C 60.851 101.830, 63.707 102.422, 66.603 100.525 C 72.504 96.658, 71.235 89.680, 64.243 87.549 C 62.523 87.024, 61.273 87.573, 58.993 89.853 M 31.155 100.393 C 30.096 101.552, 28.923 103.512, 28.548 104.749 C 27.695 107.562, 30.873 109, 37.940 109 C 49.129 109, 51.370 106.970, 46.531 101.220 C 44.353 98.630, 43.418 98.286, 38.571 98.286 C 34.263 98.286, 32.666 98.740, 31.155 100.393 M 81.923 100.077 C 79.956 102.044, 78.361 106.695, 79.324 107.657 C 80.398 108.731, 92.515 109.207, 95.932 108.310 C 100.301 107.162, 100.583 106.162, 97.734 101.932 C 95.571 98.722, 95.046 98.490, 89.609 98.352 C 85.125 98.238, 83.367 98.633, 81.923 100.077 M 53.803 105.961 C 50.780 108.985, 49.933 110.551, 50.179 112.664 C 50.449 114.981, 51.139 115.560, 54.500 116.292 C 59.447 117.370, 74.819 116.581, 76.222 115.178 C 77.873 113.527, 76.162 108.962, 72.531 105.331 C 69.537 102.337, 68.888 102.092, 66.422 103.030 C 64.518 103.754, 62.748 103.759, 60.706 103.047 C 57.993 102.101, 57.423 102.342, 53.803 105.961",
                    fill: '#3c444b'
                }
            ),
            el(
                'path',
                { 
                    d: "M 35.315 20.007 C 34.114 20.491, 32.651 21.783, 32.065 22.878 C 31.383 24.153, 31 35.134, 31 53.434 L 31 82 39 82 L 47 82 47 50.500 L 47 19 42.250 19.063 C 39.638 19.098, 36.517 19.523, 35.315 20.007 M 81 55 L 81 82 89 82 L 97 82 97 57 C 97 27.431, 97.196 28, 87 28 L 81 28 81 55 M 57.571 38.571 C 56.243 39.900, 56 43.379, 56 61.071 L 56 82 64 82 L 72 82 72 61.155 C 72 42.231, 71.831 40.157, 70.171 38.655 C 67.770 36.482, 59.714 36.428, 57.571 38.571 M 35.200 88.200 C 34.540 88.860, 34 90.570, 34 92 C 34 95.144, 35.670 97, 38.500 97 C 41.330 97, 43 95.144, 43 92 C 43 88.856, 41.330 87, 38.500 87 C 37.345 87, 35.860 87.540, 35.200 88.200 M 85.655 88.829 C 83.504 91.206, 83.560 93.291, 85.829 95.345 C 88.125 97.423, 90.405 97.452, 92.429 95.429 C 95.429 92.428, 93.275 87, 89.084 87 C 88.108 87, 86.565 87.823, 85.655 88.829 M 59 90 C 56.501 92.499, 56.416 97.161, 58.829 99.345 C 62.853 102.987, 70 99.887, 70 94.500 C 70 93.125, 69.100 91.100, 68 90 C 66.900 88.900, 64.875 88, 63.500 88 C 62.125 88, 60.100 88.900, 59 90 M 31.227 100.889 C 29.953 102.263, 29.044 104.313, 29.206 105.444 C 29.468 107.278, 30.309 107.532, 36.988 107.799 C 41.107 107.963, 45.470 107.848, 46.684 107.543 C 48.652 107.049, 48.781 106.670, 47.868 104.053 C 46.391 99.813, 44.615 98.743, 38.751 98.557 C 34.361 98.417, 33.178 98.784, 31.227 100.889 M 83.432 99.300 C 81.270 100.507, 78.668 106.068, 79.756 107.156 C 80.266 107.666, 84.686 107.951, 89.580 107.791 C 99.687 107.460, 100.923 106.347, 96.668 101.401 C 94.653 99.058, 93.428 98.565, 89.550 98.536 C 86.973 98.516, 84.220 98.860, 83.432 99.300 M 53.862 106.191 C 51.187 109.011, 49.382 113.714, 50.532 114.868 C 51.644 115.984, 71.403 116.192, 74.212 115.117 C 76.715 114.159, 76.842 113.846, 75.870 111.056 C 73.882 105.354, 70.987 103.607, 63.353 103.504 C 57.098 103.419, 56.270 103.653, 53.862 106.191",
                    fill: '#14adc4'
                }
            ),
            el(
                'path',
                { 
                    d: "M 47.446 50.500 C 47.447 68.100, 47.583 75.159, 47.749 66.187 C 47.914 57.215, 47.914 42.815, 47.748 34.187 C 47.581 25.559, 47.446 32.900, 47.446 50.500 M 30.422 45 C 30.422 56.275, 30.568 60.888, 30.746 55.250 C 30.924 49.612, 30.924 40.388, 30.746 34.750 C 30.568 29.112, 30.422 33.725, 30.422 45 M 80.452 28.266 C 80.184 28.962, 80.094 41.449, 80.250 56.016 L 80.535 82.500 80.767 55.302 L 81 28.105 86.250 27.737 L 91.500 27.370 86.219 27.185 C 82.736 27.063, 80.772 27.431, 80.452 28.266 M 97.434 57.500 C 97.433 71.250, 97.574 77.014, 97.747 70.308 C 97.919 63.603, 97.920 52.353, 97.748 45.308 C 97.576 38.264, 97.434 43.750, 97.434 57.500 M 69 37.378 C 69 37.585, 69.787 38.373, 70.750 39.128 C 72.336 40.371, 72.371 40.336, 71.128 38.750 C 69.821 37.084, 69 36.555, 69 37.378 M 55.423 61.500 C 55.424 73.050, 55.570 77.638, 55.747 71.696 C 55.924 65.753, 55.923 56.303, 55.745 50.696 C 55.567 45.088, 55.422 49.950, 55.423 61.500 M 84.872 88.750 C 83.629 90.336, 83.664 90.371, 85.250 89.128 C 86.916 87.821, 87.445 87, 86.622 87 C 86.415 87, 85.627 87.787, 84.872 88.750 M 33.158 92 C 33.158 93.375, 33.385 93.938, 33.662 93.250 C 33.940 92.563, 33.940 91.438, 33.662 90.750 C 33.385 90.063, 33.158 90.625, 33.158 92 M 43.158 92 C 43.158 93.375, 43.385 93.938, 43.662 93.250 C 43.940 92.563, 43.940 91.438, 43.662 90.750 C 43.385 90.063, 43.158 90.625, 43.158 92 M 56.158 95 C 56.158 96.375, 56.385 96.938, 56.662 96.250 C 56.940 95.563, 56.940 94.438, 56.662 93.750 C 56.385 93.063, 56.158 93.625, 56.158 95 M 84 94.378 C 84 94.585, 84.787 95.373, 85.750 96.128 C 87.336 97.371, 87.371 97.336, 86.128 95.750 C 84.821 94.084, 84 93.555, 84 94.378 M 95.500 100 C 96.495 101.100, 97.535 102, 97.810 102 C 98.085 102, 97.495 101.100, 96.500 100 C 95.505 98.900, 94.465 98, 94.190 98 C 93.915 98, 94.505 98.900, 95.500 100 M 62.813 101.683 C 63.534 101.972, 64.397 101.936, 64.729 101.604 C 65.061 101.272, 64.471 101.036, 63.417 101.079 C 62.252 101.127, 62.015 101.364, 62.813 101.683 M 28 105.941 C 28 106.459, 28.445 107.157, 28.989 107.493 C 29.555 107.843, 29.723 107.442, 29.382 106.552 C 28.717 104.820, 28 104.503, 28 105.941 M 98.564 106.707 C 98.022 108.132, 98.136 108.247, 99.124 107.267 C 99.808 106.588, 100.115 105.781, 99.807 105.474 C 99.499 105.166, 98.940 105.721, 98.564 106.707 M 34.728 108.722 C 36.503 108.943, 39.653 108.946, 41.728 108.730 C 43.802 108.513, 42.350 108.333, 38.500 108.328 C 34.650 108.324, 32.952 108.501, 34.728 108.722 M 85.750 108.723 C 87.537 108.945, 90.463 108.945, 92.250 108.723 C 94.037 108.502, 92.575 108.320, 89 108.320 C 85.425 108.320, 83.963 108.502, 85.750 108.723 M 74.500 115 C 72.870 115.701, 72.812 115.872, 74.191 115.930 C 75.121 115.968, 76.160 115.550, 76.500 115 C 77.211 113.850, 77.176 113.850, 74.500 115 M 59.776 116.733 C 62.128 116.945, 65.728 116.942, 67.776 116.727 C 69.824 116.512, 67.900 116.339, 63.500 116.343 C 59.100 116.346, 57.424 116.522, 59.776 116.733",
                    fill: '#34515b'
                }
            )           
        )
    );

    var supports = {
        customClassName: false
    };

    var keywords = ['Voting', 'Poll', 'Election', 'Online Poll', 'Opinion Poll'];
     
    registerBlockType('poll-maker/poll', {
        title: __('Poll Maker'),
        category: 'common',
        icon: iconEl,
        supports: supports,
        keywords: keywords,
        edit: withSelect(function(select) {
            if (select('core/blocks').getBlockType('poll-maker/poll').attributes.idner &&
                (select('core/blocks').getBlockType('poll-maker/poll').attributes.idner != undefined ||
                    select('core/blocks').getBlockType('poll-maker/poll').attributes.idner != null)) {
                return {
                    polls: select('core/blocks').getBlockType('poll-maker/poll').attributes.idner
                };
            } else {
                return {
                    polls: __("Something went wrong please reload page")
                };
            }
        })(function(props) {
            if (!props.polls) {
                return __("Loading...");
            }
            if (typeof props.polls != "object") {
                return props.polls;
            }

            if (props.polls.length === 0) {
                return __("There are no polls yet");
            }

            var status = 0;
            if(props.attributes.metaFieldValue > 0){            
                status = 1;
            }

            var pollner = [];
            pollner.push({
                label: __("-Select Poll-"),
                value: '0'
            });
            for (let i in props.polls) {
                let pollData = {
                    value: props.polls[i].id,
                    label: props.polls[i].title,
                }
                pollner.push(pollData)
            }            
            var aysElement = el(
                aysSelect, {
                    className: 'ays_poll_maker_block_select',
                    label: __("Select Poll"),
                    value: props.attributes.metaFieldValue,
                    onChange: function(content) {
                        var c = content;
                        if(isNaN(content)){
                            c = '';
                        }
                        status = 1;
                        wp.data.dispatch('core/editor').updateBlockAttributes(props.clientId, {
                            shortcode: "[ays_poll id=" + c + "]",
                            metaFieldValue: parseInt(c)
                        });
                    },
                    options: pollner
                }
            );
            var aysElement2 = el(
            aysSelect, {
                className: 'ays_poll_maker_block_select',
                label: '',
                value: props.attributes.metaFieldValue,
                onChange: function( content ) {
                    var c = content;
                    if(isNaN(content)){
                        c = '';
                    }
                    wp.data.dispatch( 'core/editor' ).updateBlockAttributes( props.clientId, {
                        shortcode: "[ays_poll id="+c+"]",
                        metaFieldValue: parseInt(c)
                    } );                    
                    
                },
                options: pollner
            },            
        );
        var res = el(
            wp.element.Fragment,
            {},
            el(
                BlockControls,
                props
            ),
            el(
                wp.editor.InspectorControls,
                {},
                el(
                    wp.components.PanelBody,
                    {},
                    el(
                        "div",
                        {
                            className: 'ays_poll_maker_block_container',
                            key: "inspector",
                        },
                        aysElement
                    )
                )
            ),
            el(ServerSideRender, {
                key: "editable",
                block: "poll-maker/poll",
                attributes:  props.attributes
            }),
            el(
                "div",
                {
                    className: 'ays_poll_maker_block_select_poll',
                    key: "inspector",
                },
                aysElement2
            )

        );
        var res2 = el(
                wp.element.Fragment, {},
                el(
                    BlockControls,
                    props
                ),
                el(
                    wp.editor.InspectorControls, {},
                    el(
                        wp.components.PanelBody, {},
                        el(
                            "div", {
                                className: 'ays_poll_maker_block_container',
                                key: "inspector",
                            },
                            aysElement
                        )
                    )
                ),
                el(ServerSideRender, {
                    key: "editable",
                    block: "poll-maker/poll",
                    attributes: props.attributes
                })
            );
        
            if(status == 1){
                return res2;
            }else{
                return res;
            }

        }),

        save: function(e) {
            var t = e.attributes,
                n = parseInt( t.metaFieldValue );

            resolveBlocks();

            return n ? el("div", null, '[ays_poll id="'+n+'"]') : null
        }
    });

    function resolveBlocks(id){
        var blocks = id ?
            select('core/block-editor').getBlock(id).innerBlocks
            : select('core/block-editor').getBlocks();

        if ( Array.isArray(blocks) ) {
            blocks.map( function(block){
                if(block.name == 'poll-maker/poll'){
                    if (!block.isValid) {
                        var newBlock = createBlock( block.name, block.attributes, block.innerblocks);
                        dispatch('core/block-editor').replaceBlock( block.clientId, newBlock );
                    } else {
                        resolveBlocks(block.clientId)
                    };
                }
            } );
        };
    };
})(wp);