/*
 * Settings shared by the villa's modules. Kept apart so the controls and the
 * still frames can use them without downloading Three.js.
 */
export const REST = -0.55; // first view: three-quarters, showing the front and the cantilever
export const VIEWS = 4; // the villa turns between four views, a quarter turn apart
export const FRAME_ASPECT = 16 / 11; // shape of the still frames; other shapes add room around them
export const FRAME_COUNT = 36; // still frames all the way round, one every 10° (a multiple of VIEWS)
