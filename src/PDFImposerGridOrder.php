<?php

namespace NBDImposer;

abstract class PDFImposerGridOrder
{
    const LeftRight_TopBottom = 1;
    const RightLeft_TopBottom = 2;
    const LeftRight_BottomTop = 3;
    const RightLeft_BottomTop = 4;
    const TopBottom_LeftRight = 5;
    const TopBottom_RightLeft = 6;
    const BottomTop_LeftRight = 7;
    const BottomTop_RightLeft = 8;
}