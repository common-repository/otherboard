const { PluginPostStatusInfo } = wp.editPost;
const { __ } = wp.i18n;
const { registerPlugin } = wp.plugins;
const el = wp.element.createElement;

registerPlugin( 'otherboard-plugin', {
    render: function () {
        return el(
            PluginPostStatusInfo,
            {
                name: 'link-to-otherboard',
                icon: 'admin-post',
                title: 'Otherboard',
            },
            el(
                'a',
                {
                    className: 'ob-link',
                    href: 'https://app.otherboard.com/wordpress_post/' + wp.data.select("core/editor").getCurrentPostId(),
                    target: 'new',
                    style: {
                        display: 'flex',
                    }
                },
                el(
                    'svg',
                    {
                        viewBox: '201.462 69.649 195.553 191.469',
                        style: {
                            width: '30px',
                            backgroundColor: '#14c57e',
                            padding: '5px',
                            margin: '5px 0 5px',
                            borderRadius: '100%',
                        }
                    },
                    el(
                        'path',
                        {
                            fillRule: 'evenodd',
                            clipRule: 'evenodd',
                            d: 'M 293.479 168.286 L 269.557 79.004 C 242.621 88.183 221.492 109.662 212.864 137.027 C 203.634 166.304 209.997 198.271 229.726 221.787 C 249.463 245.302 279.838 257.109 310.272 253.103 C 338.716 249.359 363.539 232.285 377.257 207.351 L 293.479 168.286 Z M 385.396 204.538 C 384.549 206.355 383.648 208.139 382.696 209.887 C 368.104 236.675 341.527 255.03 311.054 259.041 C 278.59 263.314 246.191 250.721 225.139 225.637 C 204.094 200.554 197.306 166.455 207.152 135.226 C 216.395 105.909 239.09 82.924 268.003 73.207 C 269.89 72.573 271.803 71.996 273.74 71.477 L 298.54 164.038 L 385.396 204.538 Z',
                            style: {
                                fill: 'rgb(255, 255, 255)'
                            }
                        }
                    ),
                    el(
                        'path',
                        {
                            fillRule: 'evenodd',
                            clipRule: 'evenodd',
                            d: 'M 292.418 169.176 L 268.694 80.635 C 242.74 89.92 222.433 110.852 214.061 137.404 C 204.96 166.272 211.234 197.792 230.687 220.98 C 250.149 244.167 280.099 255.809 310.108 251.859 C 337.708 248.226 361.849 231.865 375.501 207.916 L 292.418 169.176 Z M 377.257 207.351 C 377.257 207.351 377.257 207.35 377.257 207.351 C 377.055 207.718 376.849 208.084 376.642 208.449 C 362.806 232.786 338.296 249.414 310.272 253.103 C 279.838 257.109 249.463 245.302 229.726 221.787 C 209.997 198.271 203.634 166.304 212.864 137.027 C 221.365 110.066 242 88.818 268.368 79.419 C 268.763 79.278 269.159 79.14 269.557 79.004 L 293.479 168.286 L 377.257 207.351 Z M 387.065 203.931 L 386.534 205.068 C 385.675 206.91 384.762 208.717 383.798 210.487 C 369.015 237.626 342.09 256.221 311.218 260.286 C 278.329 264.615 245.506 251.856 224.177 226.444 C 202.857 201.032 195.98 166.487 205.955 134.848 C 215.319 105.147 238.312 81.862 267.603 72.018 C 269.514 71.375 271.452 70.79 273.415 70.264 L 274.627 69.939 L 299.602 163.148 L 387.065 203.931 Z M 298.54 164.038 L 273.74 71.477 C 273.335 71.585 272.932 71.696 272.53 71.809 C 271.005 72.239 269.496 72.706 268.003 73.207 C 239.09 82.924 216.395 105.909 207.152 135.226 C 197.306 166.455 204.094 200.554 225.139 225.637 C 246.191 250.721 278.59 263.314 311.054 259.041 C 341.527 255.03 368.104 236.675 382.696 209.887 C 383.449 208.504 384.171 207.099 384.859 205.672 C 385.035 205.308 385.208 204.942 385.379 204.575 C 385.385 204.563 385.391 204.55 385.396 204.538 L 298.54 164.038 Z',
                            style: {
                                fill: 'rgb(255, 255, 255)'
                            }
                        }
                    ),
                    el(
                        'path',
                        {
                            fillRule: 'evenodd',
                            clipRule: 'evenodd',
                            d: 'M 369.266 97.646 L 374.871 103.246 C 385.843 114.206 393.171 128.28 395.868 143.553 L 395.868 143.554 C 398.558 158.828 396.493 174.56 389.94 188.617 L 386.582 195.823 L 307.831 159.079 L 369.266 97.646 Z M 320.758 156.802 L 382.939 185.813 L 383.115 185.436 C 389.014 172.781 390.874 158.616 388.452 144.862 M 320.758 156.802 L 369.269 108.294 L 369.549 108.573 C 379.425 118.439 386.023 131.109 388.452 144.861',
                            style: {
                                fill: 'rgb(255, 255, 255)'
                            }
                        }
                    ),
                    el(
                        'path',
                        {
                            d: 'M 283.643 72.507 L 284.484 75.646 L 288.691 71.302 C 286.984 71.612 285.313 72.06 283.643 72.507 Z M 298.752 69.885 L 286.338 82.566 L 288.392 90.231 L 308.422 69.704 C 305.199 69.591 301.967 69.651 298.745 69.887 L 298.752 69.885 Z M 316.431 70.434 L 290.291 97.32 L 292.344 104.985 L 324.468 71.94 C 321.815 71.295 319.129 70.79 316.428 70.429 L 316.431 70.434 Z M 331.182 73.968 L 294.201 111.911 L 296.255 119.576 L 338.009 76.682 C 335.779 75.652 333.498 74.744 331.173 73.964 L 331.182 73.968 Z M 343.814 79.659 L 298.175 126.657 L 300.229 134.322 L 349.68 83.388 C 347.792 82.042 345.83 80.793 343.805 79.654 L 343.814 79.659 Z M 354.641 87.237 L 302.083 141.328 L 304.131 148.972 L 359.614 91.881 C 358.043 90.24 356.38 88.693 354.641 87.237 Z',
                            style: {
                                fill: 'rgb(255, 255, 255)'
                            }
                        }
                    )
                ),
                el('span', { style: { alignSelf: 'center', marginLeft: '5px' } }, 'Go to Otherboard'),
            )
            )
        }
} );