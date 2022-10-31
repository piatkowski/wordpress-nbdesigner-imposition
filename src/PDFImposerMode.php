<?php

namespace NBDImposer;

abstract class PDFImposerMode
{
    const FRONT = 1;
    const FRONT_BACK = 2;
    const MULTIPAGE = 3;
    const MULTIPAGE_NOT_PERSONALIZED = 4;
}