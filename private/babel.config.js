const presets = [
  [
    '@babel/preset-env',
    {
      corejs: '3.41',
      useBuiltIns: 'usage',
      modules: 'auto',
      shippedProposals: true,
    },
  ]
];

const plugins = [
  ['@babel/plugin-transform-optional-chaining'],
  ['@babel/plugin-transform-class-properties'],
  ['@babel/plugin-proposal-pipeline-operator', { proposal: 'minimal' }],
];

module.exports = {
  presets,
  plugins,
};
