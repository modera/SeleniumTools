<?php

namespace Modera\Component\SeleniumTools {
    // Overrides global "sleep" function so in tests we wouldn't have to actually wait
    function sleep() {

    }
}