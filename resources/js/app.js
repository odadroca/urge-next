import './bootstrap';

import autocomplete from './autocomplete.js';
import composer from './composer.js';
import diffViewer from './diff.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('autocomplete', autocomplete);
    Alpine.data('composer', composer);
    Alpine.data('diffViewer', diffViewer);
});
