import { TourBoxEngine } from './tourbox-engine';
import { TourBoxCaptureEngine } from './capture-engine';
import { tourEditor } from './editor';
import { tourPlayer } from './player';
import { demoExperience } from './demo';

// Expose modular classes and components globally
window.TourBoxEngine = TourBoxEngine;
window.TourBoxCaptureEngine = TourBoxCaptureEngine;
window.tourEditor = tourEditor;
window.tourPlayer = tourPlayer;
window.demoExperience = demoExperience;
