import type { CodegenConfig } from '@graphql-codegen/cli';

const config: CodegenConfig = {
  overwrite: true,
  schema: 'http://localhost:8000/graphql',
  documents: 'resources/js/**/*.{ts,tsx}',
  generates: {
    'resources/js/types/': {
      preset: 'client',
      plugins: [],
      presetConfig: {
        gqlTagName: 'gql',
      },
      config: {
        skipTypename: false,
        avoidOptionals: {
          field: false,
          object: false,
          inputValue: false,
        },
        maybeValue: 'T | null',
        scalars: {
          DateTime: 'string',
          UUID: 'string',
          Email: 'string',
        },
      },
    },
  },
};

export default config;
