namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class BarLayout extends Component
{
    public function render(): View
    {
        return view('layouts.bar');
    }
}
