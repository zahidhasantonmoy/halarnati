#include &lt;iostream&gt;
#include &lt;iomanip&gt;
#include &lt;conio.h&gt;
#include &lt;cstdlib&gt; // For system(&quot;cls&quot;)
#include &lt;windows.h&gt; // For Sleep (Windows-specific)

using namespace std;

void displayRouteMap() {
    system(&quot;cls&quot;);
    cout &lt;&lt; setw(60) &lt;&lt; &quot;TRANSSILVA BUS ROUTE MAP\n\n\n&quot;;
    cout &lt;&lt; &quot;Bus Names and Routes:\n&quot;;
    cout &lt;&lt; &quot;1. Bus A: Jatrabari -&gt; Gulistan -&gt; Press Club -&gt; Shahbagh -&gt; Elephant Road -&gt; Kolabagan -&gt; Mohammadpur -&gt; Shamoli -&gt; Mirpur 1\n&quot;;
    cout &lt;&lt; &quot;2. Bus B: Mirpur 1 -&gt; Shamoli -&gt; Mohammadpur -&gt; Kolabagan -&gt; Elephant Road -&gt; Shahbagh -&gt; Press Club -&gt; Gulistan -&gt; Jatrabari\n&quot;;
    cout &lt;&lt; &quot;Press any key to return to the main menu...&quot;;
    getch();
}

void buyTicket() {
    int vara, Q, p, time, dev;
    dev = 1;
    char from, to, A;
    string Stopage, start, End, pros, s, P, l, sp;
    sp = &quot; &quot;;
    time = 0;
    pros = &quot;processing.&quot;;
    s = &quot;.&quot;;
    Stopage = &quot;       1. Jatrabari\n       2. Gulistan\n       3. Press Club\n       4. Shahbagh\n       5. Elephant Road\n       6. Kolabagan\n       7. Mohammadpur\n       8. Shamoli\n       9. Mirpur 1\n&quot;;
    P = &quot;dev&quot;;
    while (dev &lt; 3) {
        system(&quot;cls&quot;);
        system(&quot;color 8E&quot;);
        dev++;
        l = &quot;eloper&quot;;
        P = P + l;
        cout &lt;&lt; setw(60) &lt;&lt; &quot;WELCOME TO TRANSSILVA BUS!\n\n\n&quot;;
        cout &lt;&lt; setw(20) &lt;&lt; &quot;BUS STOPPAGES: &quot; &lt;&lt; endl &lt;&lt; setw(40) &lt;&lt; Stopage &lt;&lt; setw(120) &lt;&lt; &quot;developer_526..\n&quot; &lt;&lt; &quot;Select your location: &quot;;
        cin &gt;&gt; from;
        while (time != 1) {
            system(&quot;cls&quot;);
            cout &lt;&lt; &quot;\n\n\n\n\n\n\n\n\n\n\n\n\n&quot; &lt;&lt; setw(15) &lt;&lt; pros;
            time++;
            pros = pros + s;
            Sleep(1000); // Sleep for 1 second
        }
        time = 0;
        pros = &quot;processing.&quot;;
        system(&quot;cls&quot;);
        cout &lt;&lt; &quot;BUS STOPPAGES:\n&quot; &lt;&lt; Stopage &lt;&lt; setw(120) &lt;&lt; P &lt;&lt; endl &lt;&lt; &quot; Enter destination: &quot;;
        cin &gt;&gt; to;
        while (time != 1) {
            system(&quot;cls&quot;);
            cout &lt;&lt; &quot;\n\n\n&quot; &lt;&lt; setw(50) &lt;&lt; pros;
            time++;
            pros = pros + s;
            Sleep(1000); // Sleep for 1 second
        }
        time = 0;
        pros = &quot;processing.&quot;;
        system(&quot;cls&quot;);

        // Add your ticket logic here (from the original code)...

        A = getch();
        if (A == 13) {
            break;
        }
    }
}

int main() {
    while (true) {
        system(&quot;cls&quot;);
        cout &lt;&lt; &quot;1. View Route Map\n&quot;;
        cout &lt;&lt; &quot;2. Buy Ticket\n&quot;;
        cout &lt;&lt; &quot;3. Exit\n&quot;;
        cout &lt;&lt; &quot;Enter your choice: &quot;;
        int choice;
        cin &gt;&gt; choice;

        switch (choice) {
            case 1:
                displayRouteMap();
                break;
            case 2:
                buyTicket();
                break;
            case 3:
                cout &lt;&lt; &quot;Thank you for using TRANSSILVA BUS SERVICE!\n&quot;;
                return 0;
            default:
                cout &lt;&lt; &quot;Invalid choice! Try again.\n&quot;;
                Sleep(1000);
        }
    }
    return 0;
}
