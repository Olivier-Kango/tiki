module.exports = {
    root: true,
    env: {
        node: true,
        browser: true,
    },
    extends: [
        "eslint:recommended",
        "plugin:vue/vue3-recommended",
        "plugin:prettier/recommended"
    ],
    parserOptions: {
        parser: "@babel/eslint-parser",
        ecmaVersion: 2018,
        requireConfigFile: false,
    },
    plugins: ["prettier"],
    rules: {
        indent: ["error", 4],
        "linebreak-style": ["error", "unix"],
        "vue/no-unused-vars": "error",
        "vue/component-name-in-template-casing": ["error", "PascalCase"],
    },
};
